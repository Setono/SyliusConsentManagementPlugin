<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * It's important that this provider returns a valid widget config so it's usable in twig templates etc.
 */
interface ValidWidgetConfigProviderInterface
{
    public function getWidgetConfig(ChannelInterface $channel, string $locale): WidgetConfigInterface;
}
