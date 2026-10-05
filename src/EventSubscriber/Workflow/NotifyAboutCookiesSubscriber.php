<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow;

use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Workflow\CookieWorkflow;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Service\ResetInterface;
use Webmozart\Assert\Assert;

final class NotifyAboutCookiesSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** @var list<CookieInterface> */
    private array $cookies = [];

    public function __construct(private readonly CookieEmailManagerInterface $cookieEmailManager)
    {
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

        $this->cookies[] = $cookie;
    }

    public function notify(): void
    {
        if ([] === $this->cookies) {
            return;
        }

        $this->cookieEmailManager->sendNewCookiesEmail($this->cookies);

        $this->cookies = [];
    }

    public function reset(): void
    {
        $this->cookies = [];
    }
}
