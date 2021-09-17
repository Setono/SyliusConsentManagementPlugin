<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

use Setono\Consent\Consent;

final class ConsentCommand
{
    public bool $marketingGranted = true;

    public bool $preferencesGranted = true;

    public bool $statisticsGranted = true;

    public function __construct(?Consent $consent = null) {
        if (isset($consent)) {
            $this->marketingGranted = $consent->isMarketingConsentGranted();
            $this->preferencesGranted = $consent->isPreferencesConsentGranted();
            $this->statisticsGranted = $consent->isStatisticsConsentGranted();
        }
    }
}
