<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\ValidWidgetConfigProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    private ConsentContextInterface $consentContext;

    private ValidWidgetConfigProviderInterface $widgetConfigProvider;

    private ChannelContextInterface $channelContext;

    private LocaleContextInterface $localeContext;

    public function __construct(
        ConsentContextInterface $consentContext,
        ValidWidgetConfigProviderInterface $widgetConfigProvider,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext
    ) {
        $this->consentContext = $consentContext;
        $this->widgetConfigProvider = $widgetConfigProvider;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
    }

    public function marketingGranted(): bool
    {
        return $this->consentContext->getConsent()->isMarketingConsentGranted();
    }

    public function preferencesGranted(): bool
    {
        return $this->consentContext->getConsent()->isPreferencesConsentGranted();
    }

    public function statisticsGranted(): bool
    {
        return $this->consentContext->getConsent()->isStatisticsConsentGranted();
    }

    public function scriptTag(string $src, string ...$consents): string
    {
        return sprintf('<script type="text/plain" data-sscm-consent="%s" data-sscm-src="%s"></script>', implode('|', $consents), $src);
    }

    public function scriptTagAttributes(string ...$consents): string
    {
        return sprintf(' type="text/plain" data-sscm-consent="%s"', implode('|', $consents));
    }

    public function widgetConfig(): WidgetConfigInterface
    {
        return $this->widgetConfigProvider->getWidgetConfig(
            $this->channelContext->getChannel(),
            $this->localeContext->getLocaleCode()
        );
    }
}
