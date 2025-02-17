<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SampleCookiesServerSideSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly CookieFactoryInterface $cookieFactory,
        private readonly SampleDeciderInterface $sampleDecider,
    ) {
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

        if (!$this->sampleDecider->sample($request, SampleDeciderInterface::CONTEXT_SERVER_SIDE)) {
            return;
        }

        foreach ($request->cookies->all() as $name => $_) {
            $cookie = $this->cookieRepository->findOneByName((string) $name);
            if (null === $cookie) {
                $cookie = $this->cookieFactory->createWithData((string) $name, $request->getUri());
            }
            $cookie->incrementSamples();
            $cookie->setLastSeenAt(new \DateTimeImmutable());

            $this->cookieRepository->add($cookie);
        }
    }
}
