<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Samples the cookies of the request, but saves them when the kernel terminates, i.e. after the response has been
 * sent (with PHP-FPM), so that sampling never delays or breaks the visitor's page
 */
final class SampleCookiesServerSideSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** @var array<string, CookieInterface> */
    private array $cookies = [];

    public function __construct(
        private readonly CookieRecorderInterface $cookieRecorder,
        private readonly CookieFactoryInterface $cookieFactory,
        private readonly SampleDeciderInterface $sampleDecider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'sample',
            KernelEvents::TERMINATE => 'record',
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

        // The new cookies are created while the request is still available, because the factory resolves their URL from it
        foreach ($request->cookies->all() as $name => $_) {
            $name = (string) $name;
            $this->cookies[$name] = $this->cookieFactory->createWithName($name);
        }
    }

    public function record(): void
    {
        $cookies = $this->cookies;
        $this->reset();

        $this->cookieRecorder->record($cookies);
    }

    public function reset(): void
    {
        $this->cookies = [];
    }
}
