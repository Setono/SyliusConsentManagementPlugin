<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ConsentWidgetCookieSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConsentWidgetInterface $consentWidget,
        private readonly WidgetCookieManagerInterface $consentWidgetCookieManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 224],
            // todo add test to check that this priority is HIGHER than the priority for the Setono\SyliusConsentManagementPlugin\EventListener\FormerConsentPlatformSubscriber::onRequest
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->consentWidgetCookieManager->exists($request)) {
            return;
        }

        $this->consentWidget->setShow(false);
    }
}
