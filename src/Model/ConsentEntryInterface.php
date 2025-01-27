<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Controller\ConsentCommand;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

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

    /**
     * List of codes of consented categories
     *
     * @return list<string>
     */
    public function getConsentedCategories(): array;

    /**
     * @param list<string> $consentedCategories
     */
    public function setConsentedCategories(array $consentedCategories): void;

    public function addConsentedCategory(CategoryInterface|string $consentedCategory): void;

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void;

    public function populateFromFormerConsent(FormerConsent $formerConsent): void;
}
