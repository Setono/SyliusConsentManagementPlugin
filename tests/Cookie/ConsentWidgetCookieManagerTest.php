<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Cookie;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManager
 */
final class ConsentWidgetCookieManagerTest extends TestCase
{
    /**
     * @test
     */
    public function it_checks_for_existence(): void
    {
        $request = Request::create(
            uri: '/',
            cookies: [
                'cookie_name' => '1',
            ],
        );

        $manager = new WidgetCookieManager('cookie_name', '1');
        self::assertTrue($manager->exists($request));
    }

    /**
     * @test
     */
    public function it_writes_cookie_to_response(): void
    {
        $response = new Response();

        $manager = new WidgetCookieManager('cookie_name', '1');
        $manager->write($response);

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);

        $cookie = $cookies[0];
        self::assertSame('cookie_name', $cookie->getName());
        self::assertSame('1', $cookie->getValue());
        self::assertNull($cookie->getDomain());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertFalse($cookie->isSecure());
        self::assertFalse($cookie->isHttpOnly());
        self::assertFalse($cookie->isRaw());
        self::assertAroundOneYearFromNow($cookie->getExpiresTime());
    }

    private static function assertAroundOneYearFromNow(int $timestamp): void
    {
        $oneYearFromNow = time() + (365 * 24 * 60 * 60);
        $lowerBound = $oneYearFromNow - (60 * 60);
        $upperBound = $oneYearFromNow + (60 * 60);

        self::assertGreaterThanOrEqual($lowerBound, $timestamp);
        self::assertLessThanOrEqual($upperBound, $timestamp);
    }
}
