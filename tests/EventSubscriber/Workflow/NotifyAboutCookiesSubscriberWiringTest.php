<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow\NotifyAboutCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\Workflow\CookieWorkflow;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Boots the whole application to test the service wiring, so it shouldn't count towards code coverage
 *
 * @coversNothing
 */
final class NotifyAboutCookiesSubscriberWiringTest extends KernelTestCase
{
    /**
     * @test
     */
    public function the_same_subscriber_collects_cookies_confirms_them_on_flush_and_sends_them_on_terminate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $subscriber = $container->get(NotifyAboutCookiesSubscriber::class);
        self::assertInstanceOf(NotifyAboutCookiesSubscriber::class, $subscriber);

        // Without the doctrine.event_listener tag, postFlush() is never called and no email is ever sent
        $entityManager = $container->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertContains($subscriber, $entityManager->getEventManager()->getListeners(Events::postFlush));

        $eventDispatcher = $container->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $collectEvent = sprintf('workflow.%s.completed.%s', CookieWorkflow::NAME, CookieWorkflow::TRANSITION_CONFIRM);
        self::assertSame(['collect'], self::getListenerMethods($eventDispatcher, $collectEvent, $subscriber));
        self::assertSame(['notify'], self::getListenerMethods($eventDispatcher, KernelEvents::TERMINATE, $subscriber));
        self::assertSame(['notify'], self::getListenerMethods($eventDispatcher, ConsoleEvents::TERMINATE, $subscriber));
    }

    /**
     * Returns the methods of the given listener instance that are registered for the given event
     *
     * @return list<string>
     */
    private static function getListenerMethods(EventDispatcherInterface $eventDispatcher, string $eventName, object $listener): array
    {
        $methods = [];
        foreach ($eventDispatcher->getListeners($eventName) as $registeredListener) {
            if (is_array($registeredListener) && $registeredListener[0] === $listener && is_string($registeredListener[1])) {
                $methods[] = $registeredListener[1];
            }
        }

        return $methods;
    }
}
