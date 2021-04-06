<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\SyliusConsentManagementPlugin\Context\ConsentContextInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    private ConsentContextInterface $consentContext;

    public function __construct(ConsentContextInterface $consentContext)
    {
        $this->consentContext = $consentContext;
    }

    public function consentTag(): string
    {
        return sprintf('<script>const sscmConsent = %s</script>', json_encode($this->consentContext->get(), \JSON_THROW_ON_ERROR));
    }

    public function clientId(): string
    {
        return $this->consentContext->get()->getClientId()->toString();
    }

    public function preferencesGranted(): bool
    {
        return $this->consentContext->get()->isPreferencesGranted();
    }

    public function statisticsGranted(): bool
    {
        return $this->consentContext->get()->isStatisticsGranted();
    }

    public function marketingGranted(): bool
    {
        return $this->consentContext->get()->isMarketingGranted();
    }

    public function scriptTag(string $src, string $consent, bool $async = true): string
    {
        if ($this->consentContext->get()->isConsentGranted($consent)) {
            return sprintf('<script src="%s"%s></script>', $src, $async ? ' async' : '');
        }

        return sprintf('<script type="text/plain" data-consent="%s" src="%s"%s></script>', $consent, $src, $async ? ' async' : '');
    }

    public function scriptTagAttributes(string $consent): string
    {
        if ($this->consentContext->get()->isConsentGranted($consent)) {
            return '';
        }

        return sprintf(' type="text/plain" data-consent="%s"', $consent);
    }
}
