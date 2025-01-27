<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ConsentCheckerInterface $consentChecker,
        private readonly WidgetConfigProviderInterface $widgetConfigProvider,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function isGranted(string $consent): bool
    {
        return $this->consentChecker->isGranted($consent);
    }

    public function marketingGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_MARKETING);
    }

    public function functionalGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_FUNCTIONAL);
    }

    public function statisticalGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_STATISTICAL);
    }

    public function scriptTag(string $src, string ...$consents): string
    {
        foreach ($consents as $consent) {
            if (!$this->consentChecker->isGranted($consent)) {
                return sprintf('<script type="text/plain" data-sscm-consent="%s" data-sscm-src="%s"></script>', implode(',', $consents), $src);
            }
        }

        return sprintf('<script src="%s"></script>', $src);
    }

    public function scriptTagAttributes(string ...$consents): string
    {
        foreach ($consents as $consent) {
            if (!$this->consentChecker->isGranted($consent)) {
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
}
