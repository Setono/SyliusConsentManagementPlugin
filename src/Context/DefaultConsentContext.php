<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\SyliusConsentManagementPlugin\ClientId\ClientIdInterface;
use Setono\SyliusConsentManagementPlugin\Model\Consent;

final class DefaultConsentContext implements ConsentContextInterface
{
    private ClientIdInterface $clientId;

    public function __construct(ClientIdInterface $clientId)
    {
        $this->clientId = $clientId;
    }

    public function get(): Consent
    {
        return new Consent($this->clientId->get(), false, false, false);
    }
}
