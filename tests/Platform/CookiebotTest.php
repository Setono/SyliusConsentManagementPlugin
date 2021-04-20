<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Platform;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Platform\Cookiebot;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Platform\Cookiebot
 */
final class CookiebotTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_valid_cookie(): void
    {
        $validCookie = self::getValidCookie();

        $platform = new Cookiebot();
        self::assertTrue($platform->supports($validCookie[0], $validCookie[1]));
    }

    /**
     * @test
     */
    public function it_does_not_support_invalid_cookie_name(): void
    {
        $validCookie = self::getValidCookie();
        $platform = new Cookiebot();
        self::assertFalse($platform->supports('invalid_cookie_name', $validCookie[1]));
    }

    /**
     * @test
     */
    public function it_does_not_support_invalid_cookie_value(): void
    {
        $validCookie = self::getValidCookie();
        $platform = new Cookiebot();
        self::assertFalse($platform->supports($validCookie[0], 'invalid_cookie_value'));
    }

    /**
     * @test
     */
    public function it_gets_former_consent(): void
    {
        $validCookie = self::getValidCookie();

        $platform = new Cookiebot();
        $formerConsent = $platform->getFormerConsent($validCookie[1]);

        self::assertNotNull($formerConsent);
        self::assertTrue($formerConsent->marketingGranted);
        self::assertTrue($formerConsent->preferencesGranted);
        self::assertTrue($formerConsent->statisticsGranted);
        self::assertSame('SOyYBJ0zEzmM3TdEMGrRFeCnfXscCiPpTRrBpcgLRGIF5lKplAkvkA==', $formerConsent->clientId);
    }

    /**
     * @return array<array-key, string>
     */
    private static function getValidCookie(): array
    {
        return [
            'CookieConsent',
            '{stamp:%27SOyYBJ0zEzmM3TdEMGrRFeCnfXscCiPpTRrBpcgLRGIF5lKplAkvkA==%27%2Cnecessary:true%2Cpreferences:true%2Cstatistics:true%2Cmarketing:true%2Cver:1%2Cutc:1618899306192%2Cregion:%27dk%27}',
        ];
    }
}
