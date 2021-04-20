<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Platform;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Platform\Cookiebot;
use Setono\SyliusConsentManagementPlugin\Platform\PlatformRegistry;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Platform\PlatformRegistry
 */
final class PlatformRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_platforms(): void
    {
        $platform1 = new Cookiebot();
        $platform2 = new Cookiebot();

        $registry = new PlatformRegistry($platform1, $platform2);

        self::assertCount(2, $registry->all());
        self::assertSame($platform1, $registry->all()[0]);
        self::assertSame($platform2, $registry->all()[1]);
    }
}
