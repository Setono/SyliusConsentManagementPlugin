<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\Consent\ConsentCheckerInterface;
use Setono\SyliusConsentManagementPlugin\Event\ConsentUpdated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SessionCachedConsentChecker implements ConsentCheckerInterface, EventSubscriberInterface
{
    public function __construct(
        private readonly ConsentCheckerInterface $decorated,
        private readonly RequestStack $requestStack,
        private readonly string $sessionKey = 'sscm_consented_categories',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsentUpdated::class => 'invalidate',
        ];
    }

    public function isGranted(string $consent): bool
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasPreviousSession()) {
            return $this->decorated->isGranted($consent);
        }

        $session = $request->getSession();
        if (!$session->has($this->sessionKey)) {
            $session->set($this->sessionKey, []);
        }

        /** @var array<string, bool> $consents */
        $consents = $session->get($this->sessionKey);

        if (!array_key_exists($consent, $consents)) {
            $consents[$consent] = $this->decorated->isGranted($consent);
            $session->set($this->sessionKey, $consents);
        }

        return $consents[$consent];
    }

    public function invalidate(): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasPreviousSession()) {
            return;
        }

        $request->getSession()->remove($this->sessionKey);
    }
}
