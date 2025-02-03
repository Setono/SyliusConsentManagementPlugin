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
            new TwigFunction('sscm_should_display_widget', [Runtime::class, 'shouldDisplayWidget']),
            new TwigFunction('sscm_widget', [Runtime::class, 'widget'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_widget_config', [Runtime::class, 'widgetConfig']),
            new TwigFunction('sscm_categories', [Runtime::class, 'categories']),
        ];
    }
}
