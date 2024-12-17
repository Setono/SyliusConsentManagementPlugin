<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Controller\ConsentCommand;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Symfony\Component\HttpFoundation\Request;

interface ConsentEntryInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getClientId(): ?ClientId;

    public function setClientId(ClientId $clientId): void;

    /**
     * The IP of the user
     */
    public function getIp(): ?string;

    public function setIp(string $ip): void;

    /**
     * The URL where the user consented
     */
    public function getUrl(): ?string;

    public function setUrl(string $url): void;

    /**
     * The user agent of the user
     */
    public function getUserAgent(): ?string;

    public function setUserAgent(string $userAgent): void;

    public function isPreferencesGranted(): bool;

    public function setPreferencesGranted(bool $preferencesGranted): void;

    public function isStatisticsGranted(): bool;

    public function setStatisticsGranted(bool $statisticsGranted): void;

    public function isMarketingGranted(): bool;

    public function setMarketingGranted(bool $marketingGranted): void;

    public function populateFromRequest(Request $request): void;

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void;

    public function populateFromFormerConsent(FormerConsent $formerConsent): void;
}
