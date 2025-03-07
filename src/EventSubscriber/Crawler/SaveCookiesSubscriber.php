<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SaveCookiesSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** @var array<string, bool> */
    private array $discoveredCookies = [];

    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
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
        foreach ($event->client->getCookieJar()->all() as $cookie) {
            if (isset($this->discoveredCookies[$cookie->getName()])) {
                continue;
            }

            $obj = $this->cookieRepository->findOneByName($cookie->getName());
            if (null === $obj) {
                $obj = $this->cookieFactory->createFromBrowserKitCookie($cookie, $event->url);
            }

            $obj->incrementSamples();
            $obj->setLastSeenAt(new \DateTimeImmutable());

            $this->cookieRepository->add($obj);

            $this->discoveredCookies[$cookie->getName()] = true;
        }
    }

    public function reset(): void
    {
        $this->discoveredCookies = [];
    }
}
