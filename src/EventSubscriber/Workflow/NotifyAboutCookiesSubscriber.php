<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Workflow\CookieWorkflow;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Service\ResetInterface;
use Webmozart\Assert\Assert;

/**
 * Collects the cookies confirmed during the request and emails the store owner about them when the request
 * (or console command) terminates
 */
final class NotifyAboutCookiesSubscriber implements EventSubscriberInterface, ResetInterface, LoggerAwareInterface
{
    /**
     * Cookies confirmed during a flush that hasn't completed yet (the confirm transition is applied in a preUpdate listener)
     *
     * @var list<CookieInterface>
     */
    private array $pendingCookies = [];

    /**
     * Cookies whose confirmation has been flushed
     *
     * @var list<CookieInterface>
     */
    private array $cookies = [];

    private LoggerInterface $logger;

    public function __construct(private readonly CookieEmailManagerInterface $cookieEmailManager)
    {
        $this->logger = new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            sprintf('workflow.%s.completed.%s', CookieWorkflow::NAME, CookieWorkflow::TRANSITION_CONFIRM) => 'collect',
            KernelEvents::TERMINATE => 'notify',
            ConsoleEvents::TERMINATE => 'notify',
        ];
    }

    public function collect(Event $event): void
    {
        $cookie = $event->getSubject();
        Assert::isInstanceOf($cookie, CookieInterface::class);

        $this->pendingCookies[] = $cookie;
    }

    /**
     * Doctrine's postFlush event: only now are the confirmations persisted. If the flush fails, this isn't called
     * and the store owner isn't notified about confirmations that never happened
     */
    public function postFlush(): void
    {
        $this->cookies = array_merge($this->cookies, $this->pendingCookies);
        $this->pendingCookies = [];
    }

    public function notify(): void
    {
        $cookies = $this->cookies;
        $this->reset();

        if ([] === $cookies) {
            return;
        }

        // The notification must not break the remaining terminate listeners. The cookies are already confirmed,
        // so the store owner can still find them in the admin
        try {
            $this->cookieEmailManager->sendNewCookiesEmail($cookies);
        } catch (\Throwable $e) {
            $this->logger->error('Could not send the new cookies email: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    public function reset(): void
    {
        $this->pendingCookies = [];
        $this->cookies = [];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
