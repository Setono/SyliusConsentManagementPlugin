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
     * The length of the name column, see Resources/config/doctrine/model/Cookie.orm.xml
     */
    private const NAME_MAX_LENGTH = 255;

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

    public function record(array $cookies): void
    {
        $cookies = array_filter(
            $cookies,
            // PHP turns numeric array keys, e.g. a cookie named "123", into integers
            static fn (int|string $name): bool => '' !== (string) $name && strlen((string) $name) <= self::NAME_MAX_LENGTH,
            \ARRAY_FILTER_USE_KEY,
        );

        if ([] === $cookies) {
            return;
        }

        try {
            $this->doRecord($cookies);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request saved one of the new cookies first. With a new entity manager (the failed flush
            // closed this one), the cookie exists and is updated instead
            $this->manager = null;

            try {
                $this->doRecord($cookies);
            } catch (\Throwable $e) {
                $this->handleFailure($e);
            }
        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * @param non-empty-array<string, CookieInterface> $cookies
     */
    private function doRecord(array $cookies): void
    {
        $manager = $this->getManager();

        $existingCookies = $existingCookiesByLowercaseName = [];
        foreach ($this->findCookies($manager, array_map(strval(...), array_keys($cookies))) as $existingCookie) {
            $name = (string) $existingCookie->getName();
            $existingCookies[$name] = $existingCookie;
            $existingCookiesByLowercaseName[strtolower($name)] ??= $existingCookie;
        }

        $now = new \DateTimeImmutable();

        /** @var array<int, true> $recordedCookies */
        $recordedCookies = [];

        foreach ($cookies as $name => $newCookie) {
            $name = (string) $name;

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

    private function handleFailure(\Throwable $e): void
    {
        $this->logger->error('Could not record cookies: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e,
        ]);

        // The failed entity manager is closed, or still holds the cookies that couldn't be saved, and would fail every
        // later call in the same process, e.g. the rest of a crawl. The next call gets a new one
        $this->manager = null;
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
