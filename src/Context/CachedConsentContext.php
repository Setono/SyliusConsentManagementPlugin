<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;

/**
 * This class will cache the consent for the request life cycle directly in memory
 */
final class CachedConsentContext implements ConsentContextInterface
{
    private ?Consent $consent = null;

    public function __construct(private readonly ConsentContextInterface $decorated)
    {
    }

    public function getConsent(): Consent
    {
        if (null === $this->consent) {
            $this->consent = $this->decorated->getConsent();
        }

        return $this->consent;
    }
}
