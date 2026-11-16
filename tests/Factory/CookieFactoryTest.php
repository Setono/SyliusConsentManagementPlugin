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

    /**
     * @test
     */
    public function it_creates_from_a_sample(): void
    {
        $cookie = self::getFactory()->createFromSample(['name' => '_ga', 'expires' => null]);

        self::assertSame('_ga', $cookie->getName());
        self::assertTrue($cookie->isSession());
    }

    /**
     * @test
     */
    public function it_does_not_create_from_a_sample_with_an_invalid_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        self::getFactory()->createFromSample(['name' => 'VISIT evil.example TO VERIFY YOUR STORE', 'expires' => null]);
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
