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
 */
final class RequestBasedConsentChecker implements ConsentCheckerInterface
{
    public const CONSENT_QUERY_PARAM = '_consent';

    public function __construct(
        private readonly ConsentCheckerInterface $decorated,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function isGranted(string $consent): bool
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->isGranted($consent);
        }

        /** @var mixed $consentQuery */
        $consentQuery = $request->query->all()['_consent'] ?? [];

        if ([] === $consentQuery) {
            return $this->decorated->isGranted($consent);
        }

        if (is_array($consentQuery)) {
            if (isset($consentQuery[$consent]) && is_numeric($consentQuery[$consent])) {
                return 1 === (int) $consentQuery[$consent];
            }

            return $this->decorated->isGranted($consent);
        }

        return is_numeric($consentQuery) ? 1 === (int) $consentQuery : $this->decorated->isGranted($consent);
    }
}
