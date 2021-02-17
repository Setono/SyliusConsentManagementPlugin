<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Twig;

use Setono\SyliusCookieConsentPlugin\Context\ConsentContextInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    private ConsentContextInterface $consentContext;

    public function __construct(ConsentContextInterface $consentContext)
    {
        $this->consentContext = $consentContext;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sscc_client_id', [$this, 'clientId']),
            new TwigFunction('sscc_preferences_granted', [$this, 'preferencesGranted']),
            new TwigFunction('sscc_statistics_granted', [$this, 'statisticsGranted']),
            new TwigFunction('sscc_marketing_granted', [$this, 'marketingGranted']),
        ];
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
