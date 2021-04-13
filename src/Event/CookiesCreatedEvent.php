<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;

final class CookiesCreatedEvent
{
    /**
     * @psalm-readonly
     *
     * @var array<array-key, CookieInterface>
     */
    public array $cookies;

    /**
     * @param array<array-key, CookieInterface> $cookies
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }
}
