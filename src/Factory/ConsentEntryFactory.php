<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class ConsentEntryFactory implements ConsentEntryFactoryInterface
{
    private FactoryInterface $decorated;

    public function __construct(FactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function createNew(): ConsentEntryInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, ConsentEntryInterface::class);

        return $obj;
    }

    public function createForClientId(ClientId $clientId): ConsentEntryInterface
    {
        $consentEntry = $this->createNew();
        $consentEntry->setClientId($clientId);

        return $consentEntry;
    }
}
