<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener;

use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NewCookiesNotifierSubscriber implements EventSubscriberInterface
{
    private CookieEmailManagerInterface $cookieEmailManager;

    public function __construct(CookieEmailManagerInterface $cookieEmailManager)
    {
        $this->cookieEmailManager = $cookieEmailManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CookiesCreatedEvent::class => 'notify',
        ];
    }

    public function notify(CookiesCreatedEvent $event): void
    {
        $this->cookieEmailManager->sendNewCookiesEmail($event->cookies);
    }
}
