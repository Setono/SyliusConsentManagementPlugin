<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

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
    /**
     * The sample rate can be between 0.0001 and 1. This means that it can be set to collect cookie samples
     * between every 10,000th visit and every visit
     */
    private readonly float $sampleRate;

    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly CookieFactoryInterface $cookieFactory,
        private readonly FirewallMap $firewallMap,
        /** @var array<array-key, string> $firewalls */
        private readonly array $firewalls,
        float $sampleRate,
    ) {
        Assert::greaterThanEq($sampleRate, 0.0001);
        Assert::lessThanEq($sampleRate, 1);

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
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (!$this->collectSample($request)) {
            return;
        }

        /**
         * @var string $name
         * @var mixed $value
         */
        foreach ($request->cookies->all() as $name => $value) {
            $cookie = $this->cookieRepository->findOneByName($name);
            if (null === $cookie) {
                $cookie = $this->cookieFactory->createWithData($name, $request->getUri());
            }
            $cookie->increaseSamples();
            $cookie->setLastSeenAt(new \DateTimeImmutable());

            $this->cookieRepository->add($cookie);
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
