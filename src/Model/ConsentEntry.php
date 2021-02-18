<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Model;

use Setono\SyliusCookieConsentPlugin\Controller\Action\ConsentCommand;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Symfony\Component\HttpFoundation\Request;

class ConsentEntry implements ConsentEntryInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $clientId = null;

    protected ?string $ip = null;

    protected bool $preferences = false;

    protected bool $statistics = false;

    protected bool $marketing = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function isPreferences(): bool
    {
        return $this->preferences;
    }

    public function setPreferences(bool $preferences): void
    {
        $this->preferences = $preferences;
    }

    public function isStatistics(): bool
    {
        return $this->statistics;
    }

    public function setStatistics(bool $statistics): void
    {
        $this->statistics = $statistics;
    }

    public function isMarketing(): bool
    {
        return $this->marketing;
    }

    public function setMarketing(bool $marketing): void
    {
        $this->marketing = $marketing;
    }

    public function populateFromRequest(Request $request): void
    {
        $this->ip = $request->getClientIp();
    }

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void
    {
        $this->preferences = $consentCommand->preferences;
        $this->statistics = $consentCommand->statistics;
        $this->marketing = $consentCommand->marketing;
    }
}
