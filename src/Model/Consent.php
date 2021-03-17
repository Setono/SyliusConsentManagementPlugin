<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\ClientId\ClientId;
use Webmozart\Assert\Assert;

final class Consent implements \JsonSerializable
{
    private ClientId $clientId;

    private bool $preferences;

    private bool $statistics;

    private bool $marketing;

    public function __construct(ClientId $clientId, bool $preferences, bool $statistics, bool $marketing)
    {
        $this->clientId = $clientId;
        $this->preferences = $preferences;
        $this->statistics = $statistics;
        $this->marketing = $marketing;
    }

    public function getClientId(): ClientId
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

    public function isConsentGranted(string $consent): bool
    {
        Assert::oneOf($consent, ['marketing', 'statistics', 'preferences']);

        $res = $this->{$consent};
        Assert::boolean($res);

        return $res;
    }

    public function jsonSerialize(): array
    {
        return [
            'clientId' => $this->clientId->toString(),
            'preferences' => $this->preferences,
            'statistics' => $this->statistics,
            'marketing' => $this->marketing,
        ];
    }
}
