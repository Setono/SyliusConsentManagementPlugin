<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConsentEntryRepositoryInterface extends RepositoryInterface
{
    public function findConsentFromClientId(string $clientId): ?Consent;

    public function findOneFromClientId(string $clientId): ?ConsentEntryInterface;
}
