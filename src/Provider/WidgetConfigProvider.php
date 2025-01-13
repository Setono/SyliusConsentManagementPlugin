<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

final class WidgetConfigProvider implements WidgetConfigProviderInterface
{
    public function __construct(
        private readonly WidgetConfigRepositoryInterface $widgetConfigRepository,
        private readonly WidgetConfigFactoryInterface $widgetConfigFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function getWidgetConfig(ChannelInterface $channel = null, string $locale = null): WidgetConfigInterface
    {
        $channel = $channel ?? $this->channelContext->getChannel();
        $locale = $locale ?? $this->localeContext->getLocaleCode();

        $widgetConfig = $this->widgetConfigRepository->findOneByChannelAndLocale($channel, $locale);
        if (null === $widgetConfig) {
            $widgetConfig = $this->widgetConfigFactory->createFromChannelAndLocale($channel, $locale);

            $this->widgetConfigRepository->add($widgetConfig);
        }

        return $widgetConfig;
    }
}
