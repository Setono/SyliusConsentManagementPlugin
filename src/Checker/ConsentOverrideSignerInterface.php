<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

/**
 * Signs consent overrides so that only trusted callers, like the crawler, can grant consent through the URL
 */
interface ConsentOverrideSignerInterface
{
    /**
     * Returns the URL with a consent override appended that grants the given consent until $expiresAt
     *
     * @param string|array<string, string> $consent the value of the _consent query parameter, e.g. '1' or ['marketing' => '1']
     */
    public function signUrl(string $url, string|array $consent, \DateTimeInterface $expiresAt): string;

    /**
     * Returns true if the override has a valid signature that hasn't expired
     */
    public function verify(ConsentOverride $override): bool;
}
