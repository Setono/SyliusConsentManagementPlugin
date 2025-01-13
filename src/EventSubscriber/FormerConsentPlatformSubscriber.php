<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\SyliusConsentManagementPlugin\Cookie\ConsentWidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Platform\PlatformRegistryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// todo implement ResetInterface
final class FormerConsentPlatformSubscriber implements EventSubscriberInterface
{
    private bool $setConsentWidgetCookie = false;

    /** @var array<array-key, string> */
    private array $cookiesToClear = [];

    public function __construct(
        private readonly PlatformRegistryInterface $platformRegistry,
        private readonly ConsentWidgetInterface $consentWidget,
        private readonly ClientIdProviderInterface $clientIdProvider,
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
        private readonly ConsentEntryFactoryInterface $consentEntryFactory,
        private readonly ConsentWidgetCookieManagerInterface $consentWidgetCookieManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 192],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->consentWidget->show()) {
            return;
        }

        $request = $event->getRequest();
        $cookies = self::getCookiesFromRequest($request);

        if (count($cookies) === 0) {
            return;
        }

        $formerConsent = null;

        foreach ($this->platformRegistry->all() as $platform) {
            foreach ($cookies as $name => $value) {
                if ($platform->supports($name, $value)) {
                    $formerConsent = $platform->getFormerConsent($value);
                    if (null !== $formerConsent) {
                        $this->cookiesToClear[] = $name;

                        break 2;
                    }
                }
            }
        }

        if (null === $formerConsent) {
            return;
        }

        $this->consentWidget->setShow(false);
        $this->setConsentWidgetCookie = true;

        $clientId = $this->clientIdProvider->getClientId();
        if (null !== $this->consentEntryRepository->findOneFromClientId($clientId)) {
            return;
        }

        $consentEntry = $this->consentEntryFactory->createFromRequest($request);
        $consentEntry->populateFromFormerConsent($formerConsent);
        $consentEntry->setClientId($clientId);

        $this->consentEntryRepository->add($consentEntry);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        foreach ($this->cookiesToClear as $cookieName) {
            $response->headers->clearCookie($cookieName);
        }

        if ($this->setConsentWidgetCookie) {
            $this->consentWidgetCookieManager->write($response);
        }
    }

    /**
     * @return array<string, string>
     */
    private static function getCookiesFromRequest(Request $request): array
    {
        $cookies = [];

        foreach ($request->cookies->all() as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }

            $cookies[$name] = $value;
        }

        return $cookies;
    }
}
