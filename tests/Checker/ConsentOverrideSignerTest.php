<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;

final class ConsentOverrideSignerTest extends TestCase
{
    /**
     * @test
     */
    public function it_accepts_its_own_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertTrue($signer->isSigned(self::createRequest($signer->sign('1', new \DateTimeImmutable('+1 hour')))));
        self::assertTrue($signer->isSigned(self::createRequest($signer->sign(['marketing' => '1', 'statistical' => '0'], new \DateTimeImmutable('+1 hour')))));
    }

    /**
     * @test
     */
    public function it_rejects_expired_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->isSigned(self::createRequest($signer->sign('1', new \DateTimeImmutable('-1 second')))));
    }

    /**
     * @test
     */
    public function it_rejects_tampered_overrides(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        $query = $signer->sign(['statistical' => '1'], new \DateTimeImmutable('+1 hour'));
        $query['_consent'] = ['statistical' => '1', 'marketing' => '1'];
        self::assertFalse($signer->isSigned(self::createRequest($query)));

        $query = $signer->sign('1', new \DateTimeImmutable('+1 hour'));
        $query['_consent_expires'] = (string) strtotime('+1 year');
        self::assertFalse($signer->isSigned(self::createRequest($query)));
    }

    /**
     * @test
     */
    public function it_rejects_missing_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->isSigned(self::createRequest(['_consent' => '1'])));
        self::assertFalse($signer->isSigned(self::createRequest(['_consent' => '1', '_consent_expires' => (string) strtotime('+1 hour')])));
    }

    /**
     * @test
     */
    public function it_rejects_signatures_made_by_the_uri_signer_with_the_same_secret(): void
    {
        $expires = (string) strtotime('+1 hour');

        // Asked to sign this message as a URI, the uri_signer appends ?_hash=<base64 HMAC-SHA256 of the message>,
        // keyed with the same secret (kernel.secret) the consent overrides are signed with
        $message = '_consent=1&_consent_expires=' . $expires;
        $signedUri = (new UriSigner('secret'))->sign($message);
        self::assertStringStartsWith($message . '?_hash=', $signedUri);

        parse_str((string) parse_url($signedUri, \PHP_URL_QUERY), $params);
        $hash = $params['_hash'] ?? null;
        self::assertIsString($hash);
        $hmac = base64_decode(strtr($hash, '-_', '+/'), true);
        self::assertIsString($hmac);

        self::assertFalse((new ConsentOverrideSigner('secret'))->isSigned(self::createRequest([
            '_consent' => '1',
            '_consent_expires' => $expires,
            '_consent_signature' => bin2hex($hmac),
        ])));
    }

    /**
     * @test
     */
    public function it_ignores_the_arg_separator_output_setting(): void
    {
        $signer = new ConsentOverrideSigner('secret');
        $request = self::createRequest($signer->sign('1', new \DateTimeImmutable('+1 hour')));

        // The crawler signs on the CLI and the shop verifies on FPM, and their ini settings can differ
        $argSeparator = ini_set('arg_separator.output', '&amp;');

        try {
            self::assertTrue($signer->isSigned($request));
        } finally {
            ini_set('arg_separator.output', (string) $argSeparator);
        }
    }

    /**
     * @test
     */
    public function it_requires_a_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConsentOverrideSigner('');
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function createRequest(array $query): Request
    {
        // Build the request from the URL, like a real request, so the query values are strings
        return Request::create('https://shop.example.com/en_US/?' . http_build_query($query, '', '&'));
    }
}
