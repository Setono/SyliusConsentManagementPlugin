<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\SyliusConsentManagementPlugin\Controller\Action\ConsentCommand;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Symfony\Component\HttpFoundation\Request;

class ConsentEntry implements ConsentEntryInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $clientId = null;

    protected ?string $ip = null;

    protected ?string $url = null;

    protected ?string $userAgent = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
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
        $this->ip = (string) $request->getClientIp();

        $this->url = $request->getUri();
        if ($request->isXmlHttpRequest() && $request->headers->has('referer')) {
            $this->url = $request->headers->get('referer');
        }

        $userAgent = $request->headers->get('user-agent');
        if (is_string($userAgent)) {
            $this->userAgent = $userAgent;
        }
    }

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void
    {
        $this->preferences = $consentCommand->preferences;
        $this->statistics = $consentCommand->statistics;
        $this->marketing = $consentCommand->marketing;
    }
}
