<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider;

use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class CookieBasedWidgetDisplayDecider implements WidgetDisplayDeciderInterface
{
    public function __construct(private readonly WidgetCookieManagerInterface $widgetCookieManager)
    {
    }

    public function display(Request $request): bool
    {
        return !$this->widgetCookieManager->exists($request);
    }
}
