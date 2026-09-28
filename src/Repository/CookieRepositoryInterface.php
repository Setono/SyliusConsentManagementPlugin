<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CookieRepositoryInterface extends RepositoryInterface
{
    public function findOneByName(string $name): ?CookieInterface;

    /**
     * @param list<string> $names
     *
     * @return array<string, CookieInterface> the cookies with the given names, indexed by name
     */
    public function findByNames(array $names): array;

    /**
     * @return list<CookieInterface>
     */
    public function findStaleCookies(string $staleThreshold): array;

    public function prune(): void;
}
