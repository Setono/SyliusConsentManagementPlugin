<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WidgetExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sscm_should_display_widget', [WidgetRuntime::class, 'shouldDisplayWidget']),
            new TwigFunction('sscm_widget', [WidgetRuntime::class, 'widget'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_widget_config', [WidgetRuntime::class, 'widgetConfig']),
            new TwigFunction('sscm_widget_layout_style_tag', [WidgetRuntime::class, 'widgetLayoutStyleTag'], ['is_safe' => ['html']]),
        ];
    }
}
