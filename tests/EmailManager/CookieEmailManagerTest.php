<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EmailManager;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManager;
use Setono\SyliusConsentManagementPlugin\EmailManager\Emails;
use Sylius\Component\Mailer\Sender\SenderInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManager
 */
final class CookieEmailManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_sends(): void
    {
        $sender = $this->prophesize(SenderInterface::class);
        $sender->send(Emails::NEW_COOKIES, ['johndoe@example.com'], ['cookies' => []])
            ->shouldBeCalled()
        ;

        $emailManager = new CookieEmailManager($sender->reveal(), ['johndoe@example.com']);
        $emailManager->sendNewCookiesEmail([]);
    }
}
