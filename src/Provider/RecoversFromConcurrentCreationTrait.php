<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Setono\Doctrine\ORMTrait;

/**
 * @internal
 */
trait RecoversFromConcurrentCreationTrait
{
    use ORMTrait;

    /**
     * Call this when a flush failed because a concurrent request created the same rows first. The failed flush closed
     * the entity manager, so it is reset before $read returns the rows the other request created.
     *
     * Resetting has a cost: entities loaded earlier in the request (e.g. the channel or the cart) are no longer tracked
     * by the manager, and changes to them that weren't flushed yet are dropped.
     *
     * @template T
     *
     * @param class-string $class
     * @param callable(): (T|null) $read returns null if nothing was found
     *
     * @return T
     *
     * @throws UniqueConstraintViolationException if the manager can't be reset or $read finds nothing
     */
    private function recoverFromConcurrentCreation(
        UniqueConstraintViolationException $exception,
        string $class,
        callable $read,
    ): mixed {
        $manager = $this->managerRegistry->getManagerForClass($class);
        $managerName = null === $manager ? false : array_search($manager, $this->managerRegistry->getManagers(), true);
        if (!is_string($managerName)) {
            throw $exception;
        }

        $this->managerRegistry->resetManager($managerName);

        return $read() ?? throw $exception;
    }
}
