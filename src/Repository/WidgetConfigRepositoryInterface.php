<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<WidgetConfigInterface>
 */
interface WidgetConfigRepositoryInterface extends RepositoryInterface
{
    public function findOneByChannelAndLocale(ChannelInterface $channel, string $locale): ?WidgetConfigInterface;
}
