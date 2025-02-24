<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\CookieFactory
 */
final class CookieFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_with_name(): void
    {
        $factory = self::getFactory();
        $cookie = $factory->createWithName('name');

        self::assertSame('name', $cookie->getName());
    }

    private static function getFactory(): CookieFactoryInterface
    {
        return new CookieFactory(
            new Factory(Cookie::class),
            new CompositeChannelContext(),
            new RequestStack(),
        );
    }
}
