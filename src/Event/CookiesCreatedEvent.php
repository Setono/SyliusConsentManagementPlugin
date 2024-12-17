<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;

final class CookiesCreatedEvent
{
    public function __construct(
        /** @var list<CookieInterface> $cookies */
        public readonly array $cookies,
    ) {
    }
}
