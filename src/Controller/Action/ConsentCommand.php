<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

use Setono\Consent\Consent;

final class ConsentCommand
{
    public bool $marketingGranted;

    public bool $preferencesGranted;

    public bool $statisticsGranted;

    public function __construct(bool $marketingGranted = true, bool $preferencesGranted = true, bool $statisticsGranted = true)
    {
        $this->marketingGranted = $marketingGranted;
        $this->preferencesGranted = $preferencesGranted;
        $this->statisticsGranted = $statisticsGranted;
    }

    public static function fromConsent(Consent $consent): self
    {
        return new self(
            $consent->isMarketingConsentGranted(),
            $consent->isPreferencesConsentGranted(),
            $consent->isStatisticsConsentGranted()
        );
    }
}
