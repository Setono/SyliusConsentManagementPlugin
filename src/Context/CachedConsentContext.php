<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\SyliusConsentManagementPlugin\Model\Consent;

/**
 * This class will cache the consent for the request life cycle directly in memory
 */
final class CachedConsentContext implements ConsentContextInterface
{
    private ?Consent $consent = null;

    private ConsentContextInterface $decorated;

    public function __construct(ConsentContextInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function get(): Consent
    {
        if (null === $this->consent) {
            $this->consent = $this->decorated->get();
        }

        return $this->consent;
    }
}
