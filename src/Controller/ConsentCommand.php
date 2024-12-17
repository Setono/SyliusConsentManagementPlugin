<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Setono\ClientId\ClientId;
use Setono\Consent\Consent;

final class ConsentCommand
{
    public function __construct(
        public readonly bool $marketingGranted = true,
        public readonly bool $preferencesGranted = true,
        public readonly bool $statisticsGranted = true,
    ) {
    }

    public static function fromConsent(Consent $consent): self
    {
        return new self(
            $consent->isMarketingConsentGranted(),
            $consent->isPreferencesConsentGranted(),
            $consent->isStatisticsConsentGranted(),
        );
    }

    public function getConsent(ClientId $clientId): Consent
    {
        return new Consent($clientId, $this->marketingGranted, $this->preferencesGranted, $this->statisticsGranted);
    }
}
