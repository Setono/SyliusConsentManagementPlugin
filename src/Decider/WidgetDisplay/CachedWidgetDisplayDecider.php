<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Caches the decision per request. The cache is reset between requests (kernel.reset), which matters when the
 * kernel handles several requests, e.g. with FrankenPHP's worker mode or RoadRunner
 */
final class CachedWidgetDisplayDecider implements WidgetDisplayDeciderInterface, ResetInterface
{
    /** @var \WeakMap<Request, bool> */
    private \WeakMap $decisions;

    public function __construct(private readonly WidgetDisplayDeciderInterface $decorated)
    {
        $this->decisions = new \WeakMap();
    }

    public function display(Request $request): bool
    {
        if (!isset($this->decisions[$request])) {
            $this->decisions[$request] = $this->decorated->display($request);
        }

        return $this->decisions[$request];
    }

    public function reset(): void
    {
        $this->decisions = new \WeakMap();
    }
}
