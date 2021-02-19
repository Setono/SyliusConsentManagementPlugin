<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Model;

use Setono\SyliusCookieConsentPlugin\Controller\Action\ConsentCommand;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Symfony\Component\HttpFoundation\Request;

interface ConsentEntryInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getClientId(): ?string;

    public function setClientId(string $clientId): void;

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

    public function isPreferences(): bool;

    public function setPreferences(bool $preferences): void;

    public function isStatistics(): bool;

    public function setStatistics(bool $statistics): void;

    public function isMarketing(): bool;

    public function setMarketing(bool $marketing): void;

    public function populateFromRequest(Request $request): void;

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void;
}
