<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Renderer;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use function Symfony\Component\String\u;

final class WidgetStyleRenderer implements WidgetStyleRendererInterface
{
    /**
     * Allows typical CSS values like '#fff', 'rgba(0, 0, 0, 0.5)', '10px 20px' and 'calc(100% - 20px)', but not the
     * characters needed to break out of a declaration or the <style> tag (;, {, }, <, >, quotes and backslashes)
     */
    public const SAFE_VALUE_PATTERN = '/^[\w #%.,()+\-*\/]+$/';

    private const SAFE_KEY_PATTERN = '/^[A-Za-z0-9_]+$/';

    public function __construct(private readonly WidgetConfigProviderInterface $widgetConfigProvider)
    {
    }

    public function render(?WidgetConfigInterface $widgetConfig = null, bool $includeTag = true): string
    {
        $widgetConfig = $widgetConfig ?? $this->widgetConfigProvider->getWidgetConfig();

        // The values are rendered inside a <style> tag, so anything that could end the declaration, the rule or the tag is skipped
        $layout = array_filter($widgetConfig->getLayout(), static function (mixed $value, mixed $key): bool {
            return is_string($key) && 1 === preg_match(self::SAFE_KEY_PATTERN, $key) &&
                is_scalar($value) && '' !== $value &&
                (is_bool($value) || 1 === preg_match(self::SAFE_VALUE_PATTERN, (string) $value));
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
