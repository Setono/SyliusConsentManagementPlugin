<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Renderer;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;

interface WidgetStyleRendererInterface
{
    /**
     * Renders the CSS for the widget
     *
     * @param WidgetConfigInterface|null $widgetConfig if null, the widget config provider will be used
     * @param bool $includeTag if true, the returned CSS will be wrapped in a <style> tag
     */
    public function render(WidgetConfigInterface $widgetConfig = null, bool $includeTag = true): string;
}
