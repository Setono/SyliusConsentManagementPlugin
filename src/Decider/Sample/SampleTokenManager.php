<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

use Psr\Cache\CacheItemPoolInterface;

/**
 * A token is the time it expires, a random nonce and an HMAC signature of both, e.g. '1790000000.9f86d081….5e1b…'.
 * The nonces of used tokens are cached until the tokens expire, so that each token can only be used once
 */
final class SampleTokenManager implements SampleTokenManagerInterface
{
    private const CACHE_KEY_PREFIX = 'sscm_sample_token.';

    public function __construct(
        private readonly string $secret,
        private readonly CacheItemPoolInterface $usedTokens,
        private readonly int $ttl = 600,
    ) {
    }

    public function create(): string
    {
        $expires = time() + $this->ttl;
        $nonce = bin2hex(random_bytes(16));

        return sprintf('%d.%s.%s', $expires, $nonce, $this->sign($expires, $nonce));
    }

    public function consume(string $token): bool
    {
        $parts = explode('.', $token);
        if (3 !== count($parts) || 1 !== preg_match('/^\d+$/', $parts[0]) || 1 !== preg_match('/^[0-9a-f]{32}$/', $parts[1])) {
            return false;
        }

        [$expires, $nonce, $signature] = $parts;
        $expires = (int) $expires;

        if ($expires < time() || !hash_equals($this->sign($expires, $nonce), $signature)) {
            return false;
        }

        $usedToken = $this->usedTokens->getItem(self::CACHE_KEY_PREFIX . $nonce);
        if ($usedToken->isHit()) {
            return false;
        }

        $usedToken->set(true);
        $usedToken->expiresAt(new \DateTimeImmutable('@' . $expires));
        $this->usedTokens->save($usedToken);

        return true;
    }

    private function sign(int $expires, string $nonce): string
    {
        // The prefix separates these signatures from other signatures made with the same secret
        return hash_hmac('sha256', sprintf('setono_sylius_consent_management_sample|%d|%s', $expires, $nonce), $this->secret);
    }
}
