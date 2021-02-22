<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\SyliusConsentManagementPlugin\ClientId\ClientIdInterface;
use Setono\SyliusConsentManagementPlugin\Model\Consent;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestBasedConsentContext implements ConsentContextInterface
{
    private ConsentContextInterface $decorated;

    private RequestStack $requestStack;

    private ClientIdInterface $clientId;

    public function __construct(ConsentContextInterface $decorated, RequestStack $requestStack, ClientIdInterface $clientId)
    {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
        $this->clientId = $clientId;
    }

    public function get(): Consent
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return $this->decorated->get();
        }

        if (!$request->query->has('_consent')) {
            return $this->decorated->get();
        }

        $preferences = $statistics = $marketing = false;

        /** @var mixed $consentQuery */
        $consentQuery = $request->query->get('_consent');
        if (is_array($consentQuery)) {
            $preferences = isset($consentQuery['preferences']) && is_string($consentQuery['preferences']) && 1 === (int) $consentQuery['preferences'];
            $statistics = isset($consentQuery['statistics']) && is_string($consentQuery['statistics']) && 1 === (int) $consentQuery['statistics'];
            $marketing = isset($consentQuery['marketing']) && is_string($consentQuery['marketing']) && 1 === (int) $consentQuery['marketing'];
        } elseif (is_string($consentQuery) && 1 === (int) $consentQuery) {
            $preferences = $statistics = $marketing = true;
        }

        return new Consent($this->clientId->get(), $preferences, $statistics, $marketing);
    }
}
