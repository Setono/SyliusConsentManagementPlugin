<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Renderer;

interface WidgetRendererInterface
{
    /**
     * Will render the consent widget as HTML
     */
    public function render(): string;
}
