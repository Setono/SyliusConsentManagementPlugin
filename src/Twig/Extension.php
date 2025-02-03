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
            new TwigFunction('sscm_is_granted', [Runtime::class, 'isGranted']),
            new TwigFunction('sscm_functional_granted', [Runtime::class, 'functionalGranted']),
            new TwigFunction('sscm_marketing_granted', [Runtime::class, 'marketingGranted']),
            new TwigFunction('sscm_statistics_granted', [Runtime::class, 'statisticalGranted']),
            new TwigFunction('sscm_should_display_widget', [Runtime::class, 'shouldDisplayWidget']),
            new TwigFunction('sscm_script_tag', [Runtime::class, 'scriptTag'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_script_tag_attributes', [Runtime::class, 'scriptTagAttributes'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_widget', [Runtime::class, 'widget'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_widget_config', [Runtime::class, 'widgetConfig']),
            new TwigFunction('sscm_categories', [Runtime::class, 'categories']),
            new TwigFunction('sscm_categories_json', [Runtime::class, 'categoriesJson'], ['is_safe' => ['html']]),
        ];
    }
}
