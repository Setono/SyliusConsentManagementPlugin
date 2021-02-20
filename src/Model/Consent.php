<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

final class Consent implements \JsonSerializable
{
    private string $clientId;

    private bool $preferences;

    private bool $statistics;

    private bool $marketing;

    public function __construct(string $clientId, bool $preferences, bool $statistics, bool $marketing)
    {
        $this->clientId = $clientId;
        $this->preferences = $preferences;
        $this->statistics = $statistics;
        $this->marketing = $marketing;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function isPreferencesGranted(): bool
    {
        return $this->preferences;
    }

    public function isStatisticsGranted(): bool
    {
        return $this->statistics;
    }

    public function isMarketingGranted(): bool
    {
        return $this->marketing;
    }

    public function jsonSerialize(): array
    {
        return [
            'clientId' => $this->clientId,
            'preferences' => $this->preferences,
            'statistics' => $this->statistics,
            'marketing' => $this->marketing,
        ];
    }
}
