<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, array<array-key, ServiceInterface>>
     */
    public function findAllIndexedByCategory(): array;
}
