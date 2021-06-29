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

    public function consentTag(): string
    {
        return sprintf('<script>const sscmConsent = %s</script>', json_encode($this->consentContext->getConsent(), \JSON_THROW_ON_ERROR));
    }

    public function clientId(): string
    {
        return $this->consentContext->getConsent()->getClientId()->toString();
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

    public function scriptTag(string $src, string $consent, bool $async = true): string
    {
        if ($this->consentContext->getConsent()->isConsentGranted($consent)) {
            return sprintf('<script src="%s"%s></script>', $src, $async ? ' async' : '');
        }

        return sprintf('<script type="text/plain" data-consent="%s" src="%s"%s></script>', $consent, $src, $async ? ' async' : '');
    }

    public function scriptTagAttributes(string $consent): string
    {
        if ($this->consentContext->getConsent()->isConsentGranted($consent)) {
            return '';
        }

        return sprintf(' type="text/plain" data-consent="%s"', $consent);
    }

    public function widgetConfig(): WidgetConfigInterface
    {
        return $this->widgetConfigProvider->getWidgetConfig(
            $this->channelContext->getChannel(),
            $this->localeContext->getLocaleCode()
        );
    }
}
