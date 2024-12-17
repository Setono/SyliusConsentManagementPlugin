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
    public function __construct(
        private readonly ConsentContextInterface $consentContext,
        private readonly ValidWidgetConfigProviderInterface $widgetConfigProvider,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {
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
        foreach ($consents as $consent) {
            if (!$this->isGranted($consent)) {
                return sprintf('<script type="text/plain" data-sscm-consent="%s" data-sscm-src="%s"></script>', implode(',', $consents), $src);
            }
        }

        return sprintf('<script src="%s"></script>', $src);
    }

    public function scriptTagAttributes(string ...$consents): string
    {
        foreach ($consents as $consent) {
            if (!$this->isGranted($consent)) {
                return sprintf(' type="text/plain" data-sscm-consent="%s"', implode(',', $consents));
            }
        }

        return '';
    }

    public function widgetConfig(): WidgetConfigInterface
    {
        return $this->widgetConfigProvider->getWidgetConfig(
            $this->channelContext->getChannel(),
            $this->localeContext->getLocaleCode(),
        );
    }

    private function isGranted(string $consent): bool
    {
        return $this->consentContext->getConsent()->isConsentGranted($consent);
    }
}
