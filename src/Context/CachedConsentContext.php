<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Context;

use Setono\SyliusCookieConsentPlugin\Model\Consent;

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
