<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Event;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent
 */
final class CookiesCreatedEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates(): void
    {
        $cookie = new Cookie();
        $event = new CookiesCreatedEvent([$cookie]);

        self::assertSame([$cookie], $event->cookies);
    }
}
