<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\NewCookiesNotifierSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventSubscriber\NewCookiesNotifierSubscriber
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

        self::assertInstanceOf(EventSubscriberInterface::class, $subscriber);
    }

    /**
     * @test
     */
    public function it_subscribes(): void
    {
        self::assertSame([CookiesCreatedEvent::class => 'notify'], NewCookiesNotifierSubscriber::getSubscribedEvents());
    }
}
