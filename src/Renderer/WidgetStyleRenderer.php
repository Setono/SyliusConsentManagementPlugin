<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Renderer;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use function Symfony\Component\String\u;

final class WidgetStyleRenderer implements WidgetStyleRendererInterface
{
    public function __construct(private readonly WidgetConfigProviderInterface $widgetConfigProvider)
    {
    }

    public function render(?WidgetConfigInterface $widgetConfig = null, bool $includeTag = true): string
    {
        $widgetConfig = $widgetConfig ?? $this->widgetConfigProvider->getWidgetConfig();

        $layout = array_filter($widgetConfig->getLayout(), static function (mixed $value, mixed $key): bool {
            return is_string($key) && is_scalar($value) && '' !== $value;
        }, \ARRAY_FILTER_USE_BOTH);

        if ([] === $layout) {
            return '';
        }

        $ret = [];

        foreach ($layout as $directive => $value) {
            $ret[] = sprintf('--sscm-widget-%s: %s;', u($directive)->snake()->replace('_', '-'), self::castScalar($value));
        }

        return sprintf('<style>.sscm-widget {%s}</style>', implode('', $ret));
    }

    /**
     * @param scalar $value
     */
    private static function castScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
