<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This class is used to override the consent for a given request by appending query parameters to the URL.
 *
 * You can do it like this: ?_consent[preferences]=1&_consent[statistics]=1&_consent[marketing]=0 which will
 * grant consent for preferences and statistics, but not marketing
 *
 * and you can do it like this: ?_consent=1 which will grant consent for all categories
 *
 * and lastly you can do the opposite: ?_consent=0 which will deny consent for all categories
 */
final class RequestBasedConsentContext implements ConsentContextInterface
{
    private ConsentContextInterface $decorated;

    private RequestStack $requestStack;

    private ClientIdProviderInterface $clientIdProvider;

    public function __construct(
        ConsentContextInterface $decorated,
        RequestStack $requestStack,
        ClientIdProviderInterface $clientIdProvider
    ) {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
        $this->clientIdProvider = $clientIdProvider;
    }

    public function getConsent(): Consent
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getConsent();
        }

        if (!$request->query->has('_consent')) {
            return $this->decorated->getConsent();
        }

        $marketing = $preferences = $statistics = false;

        /** @var mixed $consentQuery */
        $consentQuery = $request->query->get('_consent');
        if (is_array($consentQuery)) {
            $marketing = isset($consentQuery['marketing']) && is_string($consentQuery['marketing']) && 1 === (int) $consentQuery['marketing'];
            $preferences = isset($consentQuery['preferences']) && is_string($consentQuery['preferences']) && 1 === (int) $consentQuery['preferences'];
            $statistics = isset($consentQuery['statistics']) && is_string($consentQuery['statistics']) && 1 === (int) $consentQuery['statistics'];
        } elseif (is_string($consentQuery)) {
            $marketing = $preferences = $statistics = 1 === (int) $consentQuery;
        }

        return new Consent($this->clientIdProvider->getClientId(), $marketing, $preferences, $statistics);
    }
}
