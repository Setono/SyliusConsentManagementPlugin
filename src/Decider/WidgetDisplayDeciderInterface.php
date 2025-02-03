<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider;

use Symfony\Component\HttpFoundation\Request;

interface WidgetDisplayDeciderInterface
{
    /**
     * Decides whether the consent widget should be displayed on the given request
     */
    public function display(Request $request): bool;
}
