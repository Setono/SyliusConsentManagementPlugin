<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class SampleCookiesSubscriber implements EventSubscriberInterface
{
    private CookieRepositoryInterface $cookieRepository;

    private CookieFactoryInterface $cookieFactory;

    private EventDispatcherInterface $eventDispatcher;

    private FirewallMap $firewallMap;

    /** @var array<array-key, string> */
    private array $firewalls;

    /**
     * The sample rate can be between 0.0001 and 1. This means that it can be set to collect cookie samples
     * between every 10,000th visit and every visit
     */
    private float $sampleRate;

    /**
     * @param array<array-key, string> $firewalls
     */
    public function __construct(
        CookieRepositoryInterface $cookieRepository,
        CookieFactoryInterface $cookieFactory,
        EventDispatcherInterface $eventDispatcher,
        FirewallMap $firewallMap,
        array $firewalls,
        float $sampleRate
    ) {
        Assert::greaterThanEq($sampleRate, 0.0001);
        Assert::lessThanEq($sampleRate, 1);

        $this->cookieRepository = $cookieRepository;
        $this->cookieFactory = $cookieFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->sampleRate = $sampleRate;
        $this->firewallMap = $firewallMap;
        $this->firewalls = $firewalls;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'sample',
        ];
    }

    public function sample(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (!$this->collectSample($request)) {
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

    private function collectSample(Request $request): bool
    {
        $sampleRateResult = random_int(1, 10000) / 10000 <= $this->sampleRate;

        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        if (null === $firewallConfig) {
            return $sampleRateResult;
        }

        return in_array($firewallConfig->getName(), $this->firewalls, true) && $sampleRateResult;
    }
}
