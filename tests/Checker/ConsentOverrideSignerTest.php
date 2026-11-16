<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverride;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;

final class ConsentOverrideSignerTest extends TestCase
{
    private const URL = 'https://shop.example.com/en_US/products/mug?gclid=abc';

    /**
     * @test
     */
    public function it_accepts_its_own_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertTrue($signer->verify(self::createOverride($signer->signUrl(self::URL, '1', new \DateTimeImmutable('+1 hour')))));
        self::assertTrue($signer->verify(self::createOverride($signer->signUrl(self::URL, ['marketing' => '1', 'statistical' => '0'], new \DateTimeImmutable('+1 hour')))));
    }

    /**
     * @test
     */
    public function it_appends_the_signed_override_to_the_url(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $request = Request::create((new ConsentOverrideSigner('secret'))->signUrl(self::URL, ['marketing' => '1'], $expiresAt));

        self::assertSame('/en_US/products/mug', $request->getPathInfo());
        self::assertSame('abc', $request->query->get('gclid'));
        self::assertSame(['marketing' => '1'], $request->query->all('_consent'));
        self::assertSame((string) $expiresAt->getTimestamp(), $request->query->get('_consent_expires'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $request->query->get('_consent_signature'));
    }

    /**
     * @test
     */
    public function it_rejects_expired_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->verify(self::createOverride($signer->signUrl(self::URL, '1', new \DateTimeImmutable('-1 second')))));
    }

    /**
     * @test
     */
    public function it_rejects_tampered_overrides(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        $url = $signer->signUrl(self::URL, ['statistical' => '1'], new \DateTimeImmutable('+1 hour'));
        self::assertFalse($signer->verify(self::createOverride($url . '&_consent[marketing]=1')));

        $url = $signer->signUrl(self::URL, '1', new \DateTimeImmutable('+1 hour'));
        self::assertFalse($signer->verify(self::createOverride((string) preg_replace('/_consent_expires=\d+/', '_consent_expires=' . strtotime('+1 year'), $url))));
    }

    /**
     * @test
     */
    public function it_rejects_missing_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->verify(new ConsentOverride('1')));
        self::assertFalse($signer->verify(new ConsentOverride('1', (int) strtotime('+1 hour'))));
    }

    /**
     * @test
     */
    public function it_rejects_signatures_made_by_the_uri_signer_with_the_same_secret(): void
    {
        $expires = (int) strtotime('+1 hour');

        // Asked to sign this message as a URI, the uri_signer appends ?_hash=<base64 HMAC-SHA256 of the message>,
        // keyed with the same secret (kernel.secret) the consent overrides are signed with
        $message = '_consent=1&_consent_expires=' . $expires;
        $signedUri = (new UriSigner('secret'))->sign($message);
        self::assertStringStartsWith($message . '?_hash=', $signedUri);

        self::assertFalse((new ConsentOverrideSigner('secret'))->verify(new ConsentOverride('1', $expires, bin2hex(self::getUriSignerHash($signedUri)))));
    }

    /**
     * @test
     */
    public function it_derives_a_key_the_uri_signer_cannot_reveal(): void
    {
        // Asked to sign the key's label, the uri_signer returns the HMAC-SHA256 of the label, keyed with the secret.
        // That must not be the key the consent overrides are signed with
        $revealedKey = self::getUriSignerHash((new UriSigner('secret'))->sign('setono_sylius_consent_management.consent_override'));

        $expires = (int) strtotime('+1 hour');
        $message = '_consent=1&_consent_expires=' . $expires;

        foreach ([$revealedKey, bin2hex($revealedKey)] as $key) {
            self::assertFalse((new ConsentOverrideSigner('secret'))->verify(new ConsentOverride('1', $expires, hash_hmac('sha256', $message, $key))));
        }
    }

    /**
     * @test
     */
    public function it_ignores_the_arg_separator_output_setting(): void
    {
        $signer = new ConsentOverrideSigner('secret');
        $consent = ['marketing' => '1', 'statistical' => '1'];

        // The crawler signs on the CLI and the shop verifies on FPM, and their ini settings can differ
        $signedWithTheDefault = $signer->signUrl(self::URL, $consent, new \DateTimeImmutable('+1 hour'));
        $argSeparator = ini_set('arg_separator.output', '&amp;');

        try {
            $signedWithAmp = $signer->signUrl(self::URL, $consent, new \DateTimeImmutable('+1 hour'));
            self::assertTrue($signer->verify(self::createOverride($signedWithTheDefault)));
        } finally {
            ini_set('arg_separator.output', (string) $argSeparator);
        }

        self::assertStringNotContainsString('&amp;', $signedWithAmp);
        self::assertTrue($signer->verify(self::createOverride($signedWithAmp)));
    }

    /**
     * @test
     */
    public function it_requires_a_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConsentOverrideSigner('');
    }

    private static function createOverride(string $url): ConsentOverride
    {
        $override = ConsentOverride::fromRequest(Request::create($url));
        self::assertNotNull($override);

        return $override;
    }

    /**
     * Returns the raw HMAC from a URI signed by the uri_signer
     */
    private static function getUriSignerHash(string $signedUri): string
    {
        parse_str((string) parse_url($signedUri, \PHP_URL_QUERY), $params);
        $hash = $params['_hash'] ?? null;
        self::assertIsString($hash);

        $hmac = base64_decode(strtr($hash, '-_', '+/'), true);
        self::assertIsString($hmac);

        return $hmac;
    }
}
