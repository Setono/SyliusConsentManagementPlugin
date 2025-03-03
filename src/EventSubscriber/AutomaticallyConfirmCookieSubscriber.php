<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

/**
 * When a cookie is created through the interface, it should be confirmed automatically
 */
final class AutomaticallyConfirmCookieSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'setono_sylius_consent_management.cookie.pre_create' => 'confirm',
        ];
    }

    public function confirm(ResourceControllerEvent $event): void
    {
        /** @var CookieInterface|mixed $cookie */
        $cookie = $event->getSubject();
        Assert::isInstanceOf($cookie, CookieInterface::class);

        $cookie->setState(CookieInterface::STATE_CONFIRMED);
    }
}
