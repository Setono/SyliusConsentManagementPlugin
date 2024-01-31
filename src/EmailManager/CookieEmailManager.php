<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EmailManager;

use Sylius\Component\Mailer\Sender\SenderInterface;

final class CookieEmailManager implements CookieEmailManagerInterface
{
    private SenderInterface $emailSender;

    /** @var array<array-key, string> */
    private array $emails;

    /**
     * @param array<array-key, string> $emails
     */
    public function __construct(SenderInterface $emailSender, array $emails)
    {
        $this->emailSender = $emailSender;
        $this->emails = $emails;
    }

    public function sendNewCookiesEmail(array $cookies): void
    {
        /** @psalm-suppress DeprecatedMethod */
        $this->emailSender->send(Emails::NEW_COOKIES, $this->emails, [
            'cookies' => $cookies,
        ]);
    }
}
