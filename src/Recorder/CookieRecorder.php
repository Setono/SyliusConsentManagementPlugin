<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Recorder;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Webmozart\Assert\Assert;

final class CookieRecorder implements CookieRecorderInterface, LoggerAwareInterface
{
    /**
     * The length of the name column, see Resources/config/doctrine/model/Cookie.orm.xml
     */
    private const NAME_MAX_LENGTH = 255;

    private LoggerInterface $logger;

    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly ManagerRegistry $managerRegistry,
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
            // A concurrent request saved one of the new cookies first. After resetting the entity manager, which the
            // failed flush closed, the cookie exists and is updated instead
            try {
                $this->resetManager();
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
        // findBy() with a list of values results in a single IN query
        $existingCookies = [];
        foreach ($this->cookieRepository->findBy(['name' => array_map('strval', array_keys($cookies))]) as $existingCookie) {
            Assert::isInstanceOf($existingCookie, CookieInterface::class);
            $existingCookies[(string) $existingCookie->getName()] = $existingCookie;
        }

        $now = new \DateTimeImmutable();

        foreach ($cookies as $name => $newCookie) {
            if (isset($existingCookies[$name])) {
                $existingCookies[$name]->incrementSamples();
                $existingCookies[$name]->setLastSeenAt($now);

                continue;
            }

            // Set (instead of incremented) so that a retry doesn't count the sample twice
            $newCookie->setSamples(1);
            $newCookie->setLastSeenAt($now);
            $manager->persist($newCookie);
        }

        // One flush for all cookies. Confirming cookies with enough samples happens during the flush (see ConfirmCookieListener)
        $manager->flush();
    }

    private function handleFailure(\Throwable $e): void
    {
        $this->logger->error('Could not record cookies: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e,
        ]);

        // A failed flush closes the entity manager, which would break any later use of it in the same request
        if (!$this->getManager()->isOpen()) {
            $this->resetManager();
        }
    }

    private function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($this->cookieRepository->getClassName());
        Assert::isInstanceOf($manager, EntityManagerInterface::class);

        return $manager;
    }

    private function resetManager(): void
    {
        $manager = $this->getManager();

        foreach ($this->managerRegistry->getManagers() as $name => $registeredManager) {
            if ($registeredManager === $manager) {
                $this->managerRegistry->resetManager($name);

                return;
            }
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
