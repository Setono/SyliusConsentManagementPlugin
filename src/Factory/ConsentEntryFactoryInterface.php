<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface ConsentEntryFactoryInterface extends FactoryInterface
{
    public function createNew(): ConsentEntryInterface;

    public function createForClientId(ClientId $clientId): ConsentEntryInterface;
}
