<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Platform;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Platform\CookieInformation;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Platform\CookieInformation
 */
final class CookieInformationTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_valid_cookie(): void
    {
        $validCookie = self::getValidCookie();

        $platform = new CookieInformation();
        self::assertTrue($platform->supports($validCookie[0], $validCookie[1]));
    }

    /**
     * @test
     */
    public function it_does_not_support_invalid_cookie_name(): void
    {
        $validCookie = self::getValidCookie();
        $platform = new CookieInformation();
        self::assertFalse($platform->supports('invalid_cookie_name', $validCookie[1]));
    }

    /**
     * @test
     */
    public function it_does_not_support_invalid_cookie_value(): void
    {
        $validCookie = self::getValidCookie();
        $platform = new CookieInformation();
        self::assertNull($platform->getFormerConsent('invalid_cookie_value'));
    }

    /**
     * @test
     */
    public function it_gets_former_consent(): void
    {
        $validCookie = self::getValidCookie();

        $platform = new CookieInformation();
        $formerConsent = $platform->getFormerConsent($validCookie[1]);

        self::assertNotNull($formerConsent);
        self::assertTrue($formerConsent->marketingGranted);
        self::assertTrue($formerConsent->preferencesGranted);
        self::assertTrue($formerConsent->statisticsGranted);
        self::assertSame('f608b9f5-7960-4a63-909e-68f11a15b326', $formerConsent->clientId);
        self::assertSame('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36', $formerConsent->userAgent);
        self::assertSame('https://www.wattoo.dk/', $formerConsent->url);
        self::assertNotNull($formerConsent->createdAt);
    }

    /**
     * @return array<array-key, string>
     */
    private static function getValidCookie(): array
    {
        return [
            'CookieInformationConsent',
            '%7B%22website_uuid%22%3A%225b176008-519d-487f-88d6-54e82ed10056%22%2C%22timestamp%22%3A%222021-10-04T12%3A41%3A26.757Z%22%2C%22consent_url%22%3A%22https%3A%2F%2Fwww.wattoo.dk%2F%22%2C%22consent_website%22%3A%22wattoo.dk%22%2C%22consent_domain%22%3A%22www.wattoo.dk%22%2C%22user_uid%22%3A%22f608b9f5-7960-4a63-909e-68f11a15b326%22%2C%22consents_approved%22%3A%5B%22cookie_cat_necessary%22%2C%22cookie_cat_functional%22%2C%22cookie_cat_statistic%22%2C%22cookie_cat_marketing%22%2C%22cookie_cat_unclassified%22%5D%2C%22consents_denied%22%3A%5B%5D%2C%22user_agent%22%3A%22Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_15_7%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F94.0.4606.61%20Safari%2F537.36%22%7D',
        ];
    }
}
