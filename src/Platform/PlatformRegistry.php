<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Platform;

final class PlatformRegistry implements PlatformRegistryInterface
{
    /** @var list<PlatformInterface> */
    private readonly array $platforms;

    public function __construct(PlatformInterface ...$platforms)
    {
        $this->platforms = $platforms;
    }

    public function all(): array
    {
        return $this->platforms;
    }
}
