<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay;

use Symfony\Component\HttpFoundation\Request;

final class SessionCachedWidgetDisplayDecider implements WidgetDisplayDeciderInterface
{
    public function __construct(
        private readonly WidgetDisplayDeciderInterface $decorated,
        private readonly string $sessionKey = 'sscm_widget',
    ) {
    }

    public function display(Request $request): bool
    {
        if (!$request->hasPreviousSession()) {
            return $this->decorated->display($request);
        }

        $session = $request->getSession();
        if ($session->has($this->sessionKey)) {
            return false;
        }

        $decision = $this->decorated->display($request);
        if ($decision) {
            return true;
        }

        $session->set($this->sessionKey, 1);

        return false;
    }
}
