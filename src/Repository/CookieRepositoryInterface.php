<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CookieRepositoryInterface extends RepositoryInterface
{
    public function findOneByName(string $name): ?CookieInterface;

    /**
     * @return list<CookieInterface>
     */
    public function findStaleCookies(string $staleThreshold): array;
}
