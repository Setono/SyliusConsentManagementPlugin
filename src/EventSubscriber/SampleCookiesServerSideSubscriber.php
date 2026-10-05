<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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
            // Before NotifyAboutCookiesSubscriber::notify() (priority 0) emails about the cookies that recording confirms
            KernelEvents::TERMINATE => ['record', 10],
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
        foreach (self::getCookieNames($request) as $name) {
            $this->cookies[$name] = $this->cookieFactory->createWithName($name);
        }
    }

    /**
     * PHP changes cookie names when it populates $_COOKIE (and thereby $request->cookies): dots and spaces become
     * underscores and 'x[y]' becomes an array under 'x'. The real names are read from the Cookie header instead
     *
     * @return list<string>
     */
    private static function getCookieNames(Request $request): array
    {
        $header = $request->headers->get('Cookie');
        if (null === $header || '' === trim($header)) {
            return array_map(strval(...), array_keys($request->cookies->all()));
        }

        $names = [];
        foreach (explode(';', $header) as $cookie) {
            // Browsers send a cookie without a name as just its value. Skipping it keeps cookie values out of the list
            if (!str_contains($cookie, '=')) {
                continue;
            }

            $name = trim(explode('=', $cookie, 2)[0]);
            if ('' !== $name) {
                $names[$name] = true;
            }
        }

        return array_map(strval(...), array_keys($names));
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
