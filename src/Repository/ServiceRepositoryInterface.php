<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<ServiceInterface>
 */
interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * @deprecated Use ServiceRepositoryInterface::findEnabledIndexedByCategory() instead. Will be removed in 0.8
     *
     * @return array<string, array<array-key, ServiceInterface>>
     */
    public function findAllIndexedByCategory(): array;

    /**
     * @return array<string, array<array-key, ServiceInterface>>
     */
    public function findEnabledIndexedByCategory(ChannelInterface $channel): array;
}
