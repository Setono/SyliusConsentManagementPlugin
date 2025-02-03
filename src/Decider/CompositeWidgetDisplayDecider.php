<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider;

use Setono\CompositeCompilerPass\CompositeService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends CompositeService<WidgetDisplayDeciderInterface>
 */
final class CompositeWidgetDisplayDecider extends CompositeService implements WidgetDisplayDeciderInterface
{
    public function display(Request $request): bool
    {
        foreach ($this->services as $service) {
            if (!$service->display($request)) {
                return false;
            }
        }

        return true;
    }
}
