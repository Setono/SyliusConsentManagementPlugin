<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EmailManager;

use Sylius\Component\Mailer\Sender\SenderInterface;

final class CookieEmailManager implements CookieEmailManagerInterface
{
    public function __construct(
        private readonly SenderInterface $emailSender,
        /** @var list<string> $emails */
        private readonly array $emails,
    ) {
    }

    public function sendNewCookiesEmail(array $cookies): void
    {
        /** @psalm-suppress DeprecatedMethod */
        $this->emailSender->send(Emails::NEW_COOKIES, $this->emails, [
            'cookies' => $cookies,
        ]);
    }
}
