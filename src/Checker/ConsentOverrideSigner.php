<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use League\Uri\Uri;
use League\Uri\UriModifier;

/**
 * Signs the consent override and its expiry, not the whole URL:
 *
 * - A consent override applies to a single request and isn't persisted, so QA appends the same signed override to
 *   page after page
 * - The scheme, host and percent-encoding of the URL the crawler's browser requests can differ from the URL the
 *   application sees, e.g. behind a TLS-terminating proxy. That would break signatures over them
 *
 * The expiry bounds the exposure of a leaked URL instead. The crawler's signatures are valid for 5 minutes
 */
final class ConsentOverrideSigner implements ConsentOverrideSignerInterface
{
    private readonly string $key;

    public function __construct(string $secret)
    {
        if ('' === $secret) {
            throw new \InvalidArgumentException('A non-empty secret is required.');
        }

        // Derive a key of our own, so that other HMAC-SHA256 signers keyed with the same secret, like Symfony's
        // uri_signer, can't be used to forge consent overrides. A plain HMAC of a label would not do, because it's
        // exactly what the uri_signer returns when asked to sign that label
        $this->key = hash_hkdf('sha256', $secret, 32, 'setono_sylius_consent_management.consent_override');
    }

    public function signUrl(string $url, string|array $consent, \DateTimeInterface $expiresAt): string
    {
        $message = self::buildMessage($consent, $expiresAt->getTimestamp());

        return (string) UriModifier::appendQuery(
            Uri::createFromString($url),
            sprintf('%s&%s=%s', $message, ConsentOverride::SIGNATURE_QUERY_PARAM, $this->computeSignature($message)),
        );
    }

    public function verify(ConsentOverride $override): bool
    {
        if (null === $override->expires || null === $override->signature || $override->expires < time()) {
            return false;
        }

        return hash_equals($this->computeSignature(self::buildMessage($override->consent, $override->expires)), $override->signature);
    }

    /**
     * The signed message is the query string of the override and its expiry, e.g. _consent=1&_consent_expires=1790000000.
     * It's built with an explicit '&' separator, so it doesn't depend on the arg_separator.output ini setting, which can
     * differ between the CLI (where the crawler signs) and FPM (where the shop verifies)
     */
    private static function buildMessage(string|array $consent, int $expires): string
    {
        return http_build_query([
            ConsentOverride::QUERY_PARAM => $consent,
            ConsentOverride::EXPIRES_QUERY_PARAM => $expires,
        ], '', '&');
    }

    private function computeSignature(string $message): string
    {
        return hash_hmac('sha256', $message, $this->key);
    }
}
