<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Locale\Model\Locale;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\WidgetConfig
 */
final class WidgetConfigTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_and_sets(): void
    {
        $channel = new Channel();
        $locale = new Locale();

        $obj = new WidgetConfig();
        $obj->setChannel($channel);
        $obj->setLocale($locale);
        $obj->setUsageDescription('usage description');

        self::assertNull($obj->getId());
        self::assertSame($channel, $obj->getChannel());
        self::assertSame($locale, $obj->getLocale());
        self::assertSame('usage description', $obj->getUsageDescription());
    }

    /**
     * @test
     */
    public function it_is_extendable(): void
    {
        $obj = new class() extends WidgetConfig {
            public function __construct()
            {
                $this->setUsageDescription('usage description');
            }
        };

        self::assertSame('usage description', $obj->getUsageDescription());
    }
}
