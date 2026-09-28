<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @experimental
 */
final class StatisticsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sscm_consent_entry_count', [StatisticsRuntime::class, 'consentEntryCount']),
            new TwigFunction('sscm_consented_count', [StatisticsRuntime::class, 'consentedCount']),
        ];
    }
}
