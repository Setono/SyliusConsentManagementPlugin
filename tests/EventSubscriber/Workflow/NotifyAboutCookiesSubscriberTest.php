<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow\NotifyAboutCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;

final class NotifyAboutCookiesSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_sends_the_collected_cookies(): void
    {
        $cookie = new Cookie();

        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail([$cookie])->shouldBeCalledOnce();

        $subscriber = new NotifyAboutCookiesSubscriber($emailManager->reveal());
        $subscriber->collect(new Event($cookie, new Marking()));
        $subscriber->notify();
        $subscriber->notify();
    }

    /**
     * @test
     */
    public function it_forgets_the_collected_cookies_on_reset(): void
    {
        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail(Argument::any())->shouldNotBeCalled();

        $subscriber = new NotifyAboutCookiesSubscriber($emailManager->reveal());
        $subscriber->collect(new Event(new Cookie(), new Marking()));
        $subscriber->reset();
        $subscriber->notify();
    }
}
