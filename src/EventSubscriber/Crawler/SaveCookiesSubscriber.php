<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SaveCookiesSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** @var array<string, bool> */
    private array $discoveredCookies = [];

    public function __construct(
        private readonly CookieRecorderInterface $cookieRecorder,
        private readonly CookieFactoryInterface $cookieFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Crawled::class => 'save',
        ];
    }

    public function save(Crawled $event): void
    {
        $cookies = [];
        foreach ($event->client->getCookieJar()->all() as $cookie) {
            // The browser keeps its cookies between the crawled pages, so each cookie is only recorded once per crawl
            if (isset($this->discoveredCookies[$cookie->getName()])) {
                continue;
            }

            $cookies[$cookie->getName()] = $this->cookieFactory->createFromBrowserKitCookie($cookie, $event->url);
            $this->discoveredCookies[$cookie->getName()] = true;
        }

        $this->cookieRecorder->record($cookies);
    }

    public function reset(): void
    {
        $this->discoveredCookies = [];
    }
}
