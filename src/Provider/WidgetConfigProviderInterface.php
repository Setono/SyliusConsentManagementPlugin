<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface WidgetConfigProviderInterface
{
    /**
     * @param ChannelInterface|null $channel if null, the channel context will be used
     * @param string|null $locale if null, the locale context will be used
     */
    public function getWidgetConfig(?ChannelInterface $channel = null, ?string $locale = null): WidgetConfigInterface;
}
