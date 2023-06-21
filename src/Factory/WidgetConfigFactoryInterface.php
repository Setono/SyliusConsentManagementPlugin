<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @extends FactoryInterface<WidgetConfigInterface>
 */
interface WidgetConfigFactoryInterface extends FactoryInterface
{
    public function createNew(): WidgetConfigInterface;

    public function createFromChannelAndLocale(ChannelInterface $channel, string $localeCode): WidgetConfigInterface;
}
