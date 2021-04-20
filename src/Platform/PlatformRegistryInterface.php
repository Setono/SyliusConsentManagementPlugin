<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Platform;

interface PlatformRegistryInterface
{
    /**
     * @return array<array-key, PlatformInterface>
     */
    public function all(): array;
}
