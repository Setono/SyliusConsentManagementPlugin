<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CookieRepositoryInterface extends RepositoryInterface
{
    /**
     * @deprecated The plugin doesn't use this method anymore, and it will be removed in the next major version. Use
     *             findOneBy(['name' => $name]) instead
     */
    public function findOneByName(string $name): ?CookieInterface;

    /**
     * @return list<CookieInterface>
     */
    public function findStaleCookies(string $staleThreshold): array;

    public function prune(): void;
}
