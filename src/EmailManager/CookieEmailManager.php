<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EmailManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;

final class CookieEmailManager implements CookieEmailManagerInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly SenderInterface $emailSender,
        private readonly ChannelRepositoryInterface $channelRepository,
        /** @var list<string> $emails */
        private readonly array $emails,
    ) {
        $this->logger = new NullLogger();
    }

    public function sendNewCookiesEmail(array $cookies): void
    {
        $emails = $this->resolveEmails();
        if ([] === $emails) {
            $this->logger->error('No emails configured for sending notification about newly found cookies');

            return;
        }

        /** @psalm-suppress DeprecatedMethod */
        $this->emailSender->send(Emails::NEW_COOKIES, $emails, [
            'cookies' => $cookies,
        ]);
    }

    /**
     * @return list<string>
     */
    private function resolveEmails(): array
    {
        if ([] !== $this->emails) {
            return $this->emails;
        }

        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findBy(['enabled' => true]) as $channel) {
            $email = $channel->getContactEmail();
            if (null !== $email) {
                return [$email];
            }
        }

        return [];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
