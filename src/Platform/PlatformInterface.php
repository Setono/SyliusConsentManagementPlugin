<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Platform;

use Setono\SyliusConsentManagementPlugin\Model\FormerConsent;

interface PlatformInterface
{
    /**
     * Returns true if the respective platform can resolve a consent from the given cookie
     */
    public function supports(string $cookieName, string $cookieValue): bool;

    /**
     * Returns a former consent given through the respective platform
     * Returns null if it's not possible to resolve it
     */
    public function getFormerConsent(string $cookieValue): ?FormerConsent;
}
