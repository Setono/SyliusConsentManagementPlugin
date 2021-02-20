<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener;

use Setono\SyliusConsentManagementPlugin\ClientId\ClientIdInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SaveClientIdSubscriber implements EventSubscriberInterface
{
    private ClientIdInterface $clientId;

    private string $cookieName;

    public function __construct(ClientIdInterface $clientId, string $cookieName)
    {
        $this->clientId = $clientId;
        $this->cookieName = $cookieName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'save',
        ];
    }

    public function save(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->setCookie(Cookie::create($this->cookieName, $this->clientId->get(), new \DateTime('+360 days')));
    }
}
