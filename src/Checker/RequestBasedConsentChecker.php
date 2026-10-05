<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\Consent\ConsentCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
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
    public const CONSENT_QUERY_PARAM = '_consent';

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
        if (null === $request) {
            return $this->decorated->isGranted($consent);
        }

        $override = self::getOverride($request, $consent);
        if (null === $override) {
            return $this->decorated->isGranted($consent);
        }

        if (false === $override || $this->debug || true === $this->consentOverrideSigner?->isSigned($request)) {
            return $override;
        }

        return $this->decorated->isGranted($consent);
    }

    /**
     * Returns true if the request grants the consent, false if it denies it, and null if it doesn't override it
     */
    private static function getOverride(Request $request, string $consent): ?bool
    {
        $consentQuery = $request->query->all()[self::CONSENT_QUERY_PARAM] ?? [];

        if (is_array($consentQuery)) {
            if (isset($consentQuery[$consent]) && is_numeric($consentQuery[$consent])) {
                return 1 === (int) $consentQuery[$consent];
            }

            return null;
        }

        return is_numeric($consentQuery) ? 1 === (int) $consentQuery : null;
    }
}
