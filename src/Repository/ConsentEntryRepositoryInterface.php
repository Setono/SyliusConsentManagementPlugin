<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\Client\Client;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConsentEntryRepositoryInterface extends RepositoryInterface
{
    public function findOneFromClient(Client $client): ?ConsentEntryInterface;
}
