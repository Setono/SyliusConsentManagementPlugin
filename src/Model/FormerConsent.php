<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use DateTimeInterface;

/**
 * This class represents a consent given through another consent management platform
 */
final class FormerConsent
{
    public bool $marketingGranted;

    public bool $preferencesGranted;

    public bool $statisticsGranted;

    public ?string $clientId = null;

    public ?string $url = null;

    public ?string $userAgent = null;

    public ?string $ip = null;

    public ?DateTimeInterface $createdAt = null;

    public function __construct(
        bool $marketingGranted,
        bool $preferencesGranted,
        bool $statisticsGranted
    ) {
        $this->marketingGranted = $marketingGranted;
        $this->preferencesGranted = $preferencesGranted;
        $this->statisticsGranted = $statisticsGranted;
    }
}
