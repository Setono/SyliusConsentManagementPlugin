<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConsentEntryRepositoryInterface extends RepositoryInterface
{
    public function findConsentFromClientId(ClientId $clientId): ?Consent;

    public function findOneFromClientId(ClientId $clientId): ?ConsentEntryInterface;
}
