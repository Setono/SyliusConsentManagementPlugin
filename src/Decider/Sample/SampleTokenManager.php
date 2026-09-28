<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

/**
 * A token is the time it expires and an HMAC signature of it, e.g. '1790000000.5e1b…'
 */
final class SampleTokenManager implements SampleTokenManagerInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 600,
    ) {
    }

    public function create(): string
    {
        $expires = time() + $this->ttl;

        return sprintf('%d.%s', $expires, $this->sign($expires));
    }

    public function isValid(string $token): bool
    {
        $parts = explode('.', $token, 2);
        if (2 !== count($parts) || 1 !== preg_match('/^\d+$/', $parts[0])) {
            return false;
        }

        [$expires, $signature] = $parts;

        if ((int) $expires < time()) {
            return false;
        }

        return hash_equals($this->sign((int) $expires), $signature);
    }

    private function sign(int $expires): string
    {
        // The prefix separates these signatures from other signatures made with the same secret
        return hash_hmac('sha256', sprintf('setono_sylius_consent_management_sample|%d', $expires), $this->secret);
    }
}
