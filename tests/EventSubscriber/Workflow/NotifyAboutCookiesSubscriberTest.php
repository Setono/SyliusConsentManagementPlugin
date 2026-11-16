<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
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
    public function it_sends_the_cookies_confirmed_in_a_completed_flush(): void
    {
        $cookie = new Cookie();

        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail([$cookie])->shouldBeCalledOnce();

        $subscriber = new NotifyAboutCookiesSubscriber($emailManager->reveal());
        $subscriber->collect(new Event($cookie, new Marking()));
        $subscriber->postFlush();
        $subscriber->notify();
        $subscriber->notify();
    }

    /**
     * @test
     */
    public function it_does_not_send_cookies_whose_confirmation_was_never_flushed(): void
    {
        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail(Argument::any())->shouldNotBeCalled();

        $subscriber = new NotifyAboutCookiesSubscriber($emailManager->reveal());
        // The confirm transition is applied during a flush that fails, so postFlush() is never called
        $subscriber->collect(new Event(new Cookie(), new Marking()));
        $subscriber->notify();
    }

    /**
     * @test
     */
    public function it_logs_instead_of_throwing_when_the_email_cannot_be_sent(): void
    {
        $emailManager = $this->prophesize(CookieEmailManagerInterface::class);
        $emailManager->sendNewCookiesEmail(Argument::any())->willThrow(new \RuntimeException('SMTP server unavailable'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error('Could not send the new cookies email: {message}', Argument::withEntry('message', 'SMTP server unavailable'))->shouldBeCalledOnce();

        $subscriber = new NotifyAboutCookiesSubscriber($emailManager->reveal());
        $subscriber->setLogger($logger->reveal());
        $subscriber->collect(new Event(new Cookie(), new Marking()));
        $subscriber->postFlush();
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
        $subscriber->postFlush();
        $subscriber->reset();
        $subscriber->notify();
    }
}
