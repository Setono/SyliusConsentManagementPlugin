<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\Consent\ConsentCheckerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This class is used to override the consent for a given request by appending query parameters to the URL.
 *
 * You do it like this: ?_consent[functional]=1&_consent[statistical]=1&_consent[marketing]=0 which will
 * grant consent for functional and statistical, but not marketing
 *
 * and you can do it like this: ?_consent=1 which will grant consent for all categories
 *
 * and lastly you can do the opposite: ?_consent=0 which will deny consent for all categories
 *
 * Denying consent always works. Granting consent only works in debug mode or when the override is signed
 * (see ConsentOverrideSignerInterface, which the crawler and the setono:sylius-consent-management:sign-consent-url
 * command use). Otherwise, anybody could link a visitor to the store with ?_consent=1 and make tracking scripts
 * load without the visitor's consent
 */
final class RequestBasedConsentChecker implements ConsentCheckerInterface
{
    public const CONSENT_QUERY_PARAM = ConsentOverride::QUERY_PARAM;

    public function __construct(
        private readonly ConsentCheckerInterface $decorated,
        private readonly RequestStack $requestStack,
        private readonly ?ConsentOverrideSignerInterface $consentOverrideSigner = null,
        private readonly bool $debug = false,
    ) {
    }

    public function isGranted(string $consent): bool
    {
        $request = $this->requestStack->getMainRequest();
        $override = null === $request ? null : ConsentOverride::fromRequest($request);
        $granted = $override?->grants($consent);

        if (null === $override || null === $granted) {
            return $this->decorated->isGranted($consent);
        }

        if (!$granted || $this->debug || true === $this->consentOverrideSigner?->verify($override)) {
            return $granted;
        }

        return $this->decorated->isGranted($consent);
    }
}
