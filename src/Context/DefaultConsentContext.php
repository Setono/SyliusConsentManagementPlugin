<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\SyliusConsentManagementPlugin\Model\Consent;

final class DefaultConsentContext implements ConsentContextInterface
{
    private ClientIdProviderInterface $clientIdProvider;

    public function __construct(ClientIdProviderInterface $clientId)
    {
        $this->clientIdProvider = $clientId;
    }

    public function get(): Consent
    {
        return new Consent($this->clientIdProvider->get(), false, false, false);
    }
}
