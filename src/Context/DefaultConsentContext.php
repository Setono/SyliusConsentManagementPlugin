<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;

final class DefaultConsentContext implements ConsentContextInterface
{
    private ClientIdProviderInterface $clientIdProvider;

    public function __construct(ClientIdProviderInterface $clientIdProvider)
    {
        $this->clientIdProvider = $clientIdProvider;
    }

    public function getConsent(): Consent
    {
        return new Consent($this->clientIdProvider->get(), false, false, false);
    }
}
