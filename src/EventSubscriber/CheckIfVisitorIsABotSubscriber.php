<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\Parser\Bot as BotParser;
use Psr\Cache\CacheItemPoolInterface;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CheckIfVisitorIsABotSubscriber implements EventSubscriberInterface
{
    private ConsentWidgetInterface $consentWidget;

    private CacheItemPoolInterface $cache;

    public function __construct(ConsentWidgetInterface $consentWidget, CacheItemPoolInterface $cache)
    {
        $this->consentWidget = $consentWidget;
        $this->cache = $cache;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'check',
        ];
    }

    public function check(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->consentWidget->show()) {
            return;
        }
        $request = $event->getRequest();

        $userAgent = $request->headers->get('user-agent');
        if (null === $userAgent) {
            return;
        }

        $botParser = new BotParser();
        $botParser->setUserAgent($userAgent);
        $botParser->discardDetails();
        $botParser->setCache(new PSR6Bridge($this->cache));
        $result = $botParser->parse();

        // not a bot
        if (null === $result) {
            return;
        }

        $this->consentWidget->setShow(false);
    }
}
