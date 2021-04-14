<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class SampleCookiesSubscriber implements EventSubscriberInterface
{
    private CookieRepositoryInterface $cookieRepository;

    private CookieFactoryInterface $cookieFactory;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * The sample rate can be between 0.0001 and 1. This means that it can be set to collect cookie samples
     * between every 10,000th visit and every visit
     */
    private float $sampleRate;

    public function __construct(
        CookieRepositoryInterface $cookieRepository,
        CookieFactoryInterface $cookieFactory,
        EventDispatcherInterface $eventDispatcher,
        float $sampleRate
    ) {
        Assert::greaterThanEq($sampleRate, 0.0001);
        Assert::lessThanEq($sampleRate, 1);

        $this->cookieRepository = $cookieRepository;
        $this->cookieFactory = $cookieFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->sampleRate = $sampleRate;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'sample',
        ];
    }

    public function sample(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (!$this->collectSample()) {
            return;
        }

        $cookies = [];

        /**
         * @var string $name
         * @var mixed $value
         */
        foreach ($request->cookies->all() as $name => $value) {
            if (null !== $this->cookieRepository->findOneByName($name)) {
                continue;
            }

            $obj = $this->cookieFactory->createWithData($name, $request->getUri());
            $this->cookieRepository->add($obj);

            $cookies[] = $obj;
        }

        if (count($cookies) > 0) {
            $this->eventDispatcher->dispatch(new CookiesCreatedEvent($cookies));
        }
    }

    private function collectSample(): bool
    {
        return random_int(1, 10000) / 10000 <= $this->sampleRate;
    }
}
