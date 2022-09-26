<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
use Setono\MainRequestTrait\MainRequestTrait;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CheckIfVisitorIsABotSubscriber implements EventSubscriberInterface
{
    use MainRequestTrait;

    private ConsentWidgetInterface $consentWidget;

    private BotDetectorInterface $botDetector;

    public function __construct(ConsentWidgetInterface $consentWidget, BotDetectorInterface $botDetector)
    {
        $this->consentWidget = $consentWidget;
        $this->botDetector = $botDetector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'check',
        ];
    }

    public function check(RequestEvent $event): void
    {
        if (!$this->isMainRequest($event) || !$this->consentWidget->show()) {
            return;
        }
        $request = $event->getRequest();

        $userAgent = $request->headers->get('user-agent');
        if (null === $userAgent) {
            return;
        }

        if (!$this->botDetector->isBotRequest($request)) {
            return;
        }

        $this->consentWidget->setShow(false);
    }
}
