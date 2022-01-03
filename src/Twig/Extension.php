<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sscm_consents', [Runtime::class, 'consents']),

            new TwigFunction('sscm_marketing_granted', [Runtime::class, 'marketingGranted']),
            new TwigFunction('sscm_preferences_granted', [Runtime::class, 'preferencesGranted']),
            new TwigFunction('sscm_statistics_granted', [Runtime::class, 'statisticsGranted']),

            new TwigFunction('sscm_script_tag', [Runtime::class, 'scriptTag'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_script_tag_attributes', [Runtime::class, 'scriptTagAttributes'], ['is_safe' => ['html']]),

            new TwigFunction('sscm_widget_config', [Runtime::class, 'widgetConfig']),
        ];
    }
}
