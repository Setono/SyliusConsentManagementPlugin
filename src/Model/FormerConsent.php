<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use DateTimeInterface;

/**
 * This class represents a consent given through another consent management platform
 */
final class FormerConsent
{
    public ?string $clientId = null;

    public ?string $url = null;

    public ?string $userAgent = null;

    public ?string $ip = null;

    public ?DateTimeInterface $createdAt = null;

    public function __construct(
        public readonly bool $marketingGranted,
        public readonly bool $preferencesGranted,
        public readonly bool $statisticsGranted,
    ) {
    }
}
