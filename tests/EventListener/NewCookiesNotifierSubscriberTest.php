<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\EventListener\NewCookiesNotifierSubscriber;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventListener\NewCookiesNotifierSubscriber
 */
final class NewCookiesNotifierSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_notifies(): void
    {
        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail([])->shouldBeCalled();

        $subscriber = new NewCookiesNotifierSubscriber($emailManager->reveal());
        $subscriber->notify(new CookiesCreatedEvent([]));
    }
}
