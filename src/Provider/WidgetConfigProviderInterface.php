<?php
declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface WidgetConfigProviderInterface
{
    public function getWidgetConfig(ChannelInterface $channel, string $locale): WidgetConfigInterface;
}
