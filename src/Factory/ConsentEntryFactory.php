<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class ConsentEntryFactory implements ConsentEntryFactoryInterface
{
    private FactoryInterface $decorated;

    public function __construct(FactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function createNew(): ConsentEntryInterface
    {
        /** @var ConsentEntryInterface $consentEntry */
        $consentEntry = $this->decorated->createNew();

        return $consentEntry;
    }

    public function createForClientId(ClientId $clientId): ConsentEntryInterface
    {
        $consentEntry = $this->createNew();
        $consentEntry->setClientId($clientId);

        return $consentEntry;
    }
}
