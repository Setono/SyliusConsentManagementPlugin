<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Repository;

use Setono\SyliusCookieConsentPlugin\Model\Consent;
use Setono\SyliusCookieConsentPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ConsentEntryRepositoryInterface extends RepositoryInterface
{
    public function findConsentFromClientId(string $clientId): ?Consent;

    public function findOneFromClientId(string $clientId): ?ConsentEntryInterface;
}
