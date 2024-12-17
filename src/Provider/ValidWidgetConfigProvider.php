<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ValidWidgetConfigProvider implements ValidWidgetConfigProviderInterface
{
    public function __construct(
        private readonly WidgetConfigRepositoryInterface $widgetConfigRepository,
        private readonly WidgetConfigFactoryInterface $widgetConfigFactory,
    ) {
    }

    public function getWidgetConfig(ChannelInterface $channel, string $locale): WidgetConfigInterface
    {
        $widgetConfig = $this->widgetConfigRepository->findOneByChannelAndLocale($channel, $locale);
        if (null === $widgetConfig) {
            $widgetConfig = $this->widgetConfigFactory->createFromChannelAndLocale($channel, $locale);
            $this->widgetConfigRepository->add($widgetConfig);
        }

        return $widgetConfig;
    }
}
