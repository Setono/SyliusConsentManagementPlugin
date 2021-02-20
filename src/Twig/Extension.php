<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\SyliusConsentManagementPlugin\Context\ConsentContextInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class Extension extends AbstractExtension implements GlobalsInterface
{
    private ConsentContextInterface $consentContext;

    public function __construct(ConsentContextInterface $consentContext)
    {
        $this->consentContext = $consentContext;
    }

    public function getGlobals(): array
    {
        return [
            'consent_context' => $this->consentContext->get(),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sscm_consent_tag', [$this, 'consentTag'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_client_id', [$this, 'clientId']),
            new TwigFunction('sscm_preferences_granted', [$this, 'preferencesGranted']),
            new TwigFunction('sscm_statistics_granted', [$this, 'statisticsGranted']),
            new TwigFunction('sscm_marketing_granted', [$this, 'marketingGranted']),
        ];
    }

    public function consentTag(): string
    {
        return sprintf('<script>const sscmConsent = %s</script>', json_encode($this->consentContext->get(), \JSON_THROW_ON_ERROR));
    }

    public function clientId(): string
    {
        return $this->consentContext->get()->getClientId();
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
}
