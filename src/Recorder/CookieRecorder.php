<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Recorder;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\Cookie\CookieName;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Webmozart\Assert\Assert;

/**
 * Cookies are recorded through an entity manager of their own, which shares the connection, configuration and event
 * manager (and thereby the Doctrine listeners) with the default entity manager. Flushing the default entity manager
 * would also save the changes the request deliberately left unsaved, e.g. a form that failed validation, and a failed
 * flush would close it for the rest of the request
 */
final class CookieRecorder implements CookieRecorderInterface, LoggerAwareInterface
{
    /**
     * A page rarely sets more cookies than this. It limits how much one request, e.g. with a forged Cookie header or
     * sample payload, can record
     */
    public const MAX_COOKIES = 50;

    /**
     * The first attempt, and one retry if a concurrent request saved one of the new cookies first
     */
    private const MAX_ATTEMPTS = 2;

    private LoggerInterface $logger;

    private ?EntityManagerInterface $manager = null;

    /**
     * @param class-string<CookieInterface> $cookieClass
     */
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $cookieClass,
    ) {
        $this->logger = new NullLogger();
    }

    public function record(iterable $cookies): void
    {
        $cookies = $this->filter($cookies);

        if (count($cookies) > self::MAX_COOKIES) {
            $this->logger->info('Recording only {max} of {count} cookies', ['max' => self::MAX_COOKIES, 'count' => count($cookies)]);

            $cookies = array_slice($cookies, 0, self::MAX_COOKIES);
        }

        if ([] === $cookies) {
            return;
        }

        $attempts = 0;

        do {
            ++$attempts;

            try {
                $this->attemptToRecord($cookies);

                return;
            } catch (\Throwable $e) {
                // The failed entity manager is closed, or still holds the cookies that couldn't be saved, and would
                // fail every later attempt and call in the same process, e.g. the rest of a crawl. They get a new one
                $this->manager = null;

                // A concurrent request saved one of the new cookies first. With a new entity manager, the next attempt
                // finds that cookie and updates it instead
                $retry = $e instanceof UniqueConstraintViolationException && $attempts < self::MAX_ATTEMPTS;
            }
        } while ($retry);

        $this->logger->error('Could not record cookies: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e,
        ]);
    }

    /**
     * Removes the cookies with a name that isn't a valid cookie name (see CookieName), and all but the first cookie with
     * each name
     *
     * @param iterable<CookieInterface> $cookies
     *
     * @return list<array{name: non-empty-string, cookie: CookieInterface}>
     */
    private function filter(iterable $cookies): array
    {
        $filtered = $seenNames = $invalidNames = [];

        foreach ($cookies as $cookie) {
            $name = $cookie->getName() ?? '';
            if (!CookieName::isValid($name)) {
                $invalidNames[] = $name;

                continue;
            }

            if (isset($seenNames[$name])) {
                continue;
            }

            $seenNames[$name] = true;
            $filtered[] = ['name' => $name, 'cookie' => $cookie];
        }

        if ([] !== $invalidNames) {
            // A store's own cookies can have such names too, so this makes it possible to notice they aren't recorded
            $this->logger->info('Skipped {count} cookies with invalid names', ['count' => count($invalidNames), 'names' => $invalidNames]);
        }

        return $filtered;
    }

    /**
     * Records the cookies with one flush. If it throws, the entity manager can't be used anymore
     *
     * @param non-empty-list<array{name: non-empty-string, cookie: CookieInterface}> $cookies
     */
    private function attemptToRecord(array $cookies): void
    {
        $manager = $this->getManager();

        $existingCookies = $existingCookiesByLowercaseName = [];
        foreach ($this->findCookies($manager, array_column($cookies, 'name')) as $existingCookie) {
            $name = (string) $existingCookie->getName();
            $existingCookies[$name] = $existingCookie;
            $existingCookiesByLowercaseName[strtolower($name)] ??= $existingCookie;
        }

        $now = new \DateTimeImmutable();

        /** @var array<int, true> $recordedCookies */
        $recordedCookies = [];

        foreach ($cookies as ['name' => $name, 'cookie' => $newCookie]) {
            // MySQL compares names case-insensitively by default. Then the query also finds the existing 'foo' for 'FOO',
            // and the unique index doesn't allow saving 'FOO' as a new cookie. Exact matches go first, because other
            // databases can have both
            $cookie = $existingCookies[$name] ?? $existingCookiesByLowercaseName[strtolower($name)] ?? null;

            if (null === $cookie) {
                $cookie = $newCookie;

                // Set (instead of incremented) so that a retry doesn't count the sample twice
                $cookie->setSamples(1);
                self::resolveRelations($manager, $cookie);
                $manager->persist($cookie);

                // For the same reason, later names that only differ in case count as this cookie
                $existingCookiesByLowercaseName[strtolower($name)] = $cookie;
            } elseif (!isset($recordedCookies[spl_object_id($cookie)])) {
                $cookie->incrementSamples();
            }

            $cookie->setLastSeenAt($now);
            $recordedCookies[spl_object_id($cookie)] = true;
        }

        try {
            // One flush for all cookies. Confirming cookies with enough samples happens during the flush (see ConfirmCookieListener)
            $manager->flush();
        } finally {
            // The next call loads the cookies again instead of working with what may be outdated by then
            $manager->clear();
        }
    }

    /**
     * @param non-empty-list<string> $names
     *
     * @return list<CookieInterface>
     */
    private function findCookies(EntityManagerInterface $manager, array $names): array
    {
        $cookies = $manager->createQueryBuilder()
            ->select('o')
            ->from($this->cookieClass, 'o')
            ->andWhere('o.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult()
        ;
        Assert::isList($cookies);
        Assert::allIsInstanceOf($cookies, CookieInterface::class);

        return $cookies;
    }

    /**
     * The factory adds entities that were loaded by the default entity manager, e.g. the current channel. The recording
     * entity manager would consider those new entities, so they are replaced with references to the same rows
     */
    private static function resolveRelations(EntityManagerInterface $manager, CookieInterface $cookie): void
    {
        $service = $cookie->getService();
        if (null !== $service) {
            $cookie->setService(self::getReference($manager, $service));
        }

        $channels = $cookie->getChannels();
        foreach ($channels->toArray() as $key => $channel) {
            $channels->set($key, self::getReference($manager, $channel));
        }
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private static function getReference(EntityManagerInterface $manager, object $entity): object
    {
        $metadata = $manager->getClassMetadata($entity::class);

        $reference = $manager->getReference($metadata->getName(), $metadata->getIdentifierValues($entity));
        Assert::notNull($reference);

        return $reference;
    }

    private function getManager(): EntityManagerInterface
    {
        if (null === $this->manager) {
            $defaultManager = $this->managerRegistry->getManagerForClass($this->cookieClass);
            Assert::isInstanceOf($defaultManager, EntityManagerInterface::class);

            $this->manager = new EntityManager(
                $defaultManager->getConnection(),
                $defaultManager->getConfiguration(),
                $defaultManager->getEventManager(),
            );
        }

        return $this->manager;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
