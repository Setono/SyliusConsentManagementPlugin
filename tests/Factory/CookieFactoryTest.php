<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Sylius\Component\Resource\Factory\Factory;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\CookieFactory
 */
final class CookieFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_with_data(): void
    {
        $factory = self::getFactory();
        $cookie = $factory->createWithData('name', 'https://example.com');

        self::assertSame('name', $cookie->getName());
        self::assertSame('https://example.com', $cookie->getUrl());
    }

    private static function getFactory(): CookieFactoryInterface
    {
        return new CookieFactory(new Factory(Cookie::class));
    }
}
