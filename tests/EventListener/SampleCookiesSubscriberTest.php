<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\EventListener\SampleCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventListener\SampleCookiesSubscriber
 */
final class SampleCookiesSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public static int $randomInt = 0;

    /**
     * @test
     */
    public function it_subscribes(): void
    {
        self::assertSame([KernelEvents::REQUEST => 'sample'], SampleCookiesSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_samples(): void
    {
        $event = $this->getRequestEvent();

        $subscriber = $this->getSubscriber();
        $subscriber->sample($event);
    }

    private function getSubscriber(): SampleCookiesSubscriber
    {
        $repository = $this->prophesize(CookieRepositoryInterface::class);
        $repository->findOneByName(Argument::type('string'))->willReturn(null, new Cookie());
        $repository->add(Argument::type(Cookie::class))->shouldBeCalledOnce();

        $factory = new CookieFactory(new Factory(Cookie::class));

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(CookiesCreatedEvent::class))->shouldBeCalled();

        return new SampleCookiesSubscriber($repository->reveal(), $factory, $eventDispatcher->reveal(), 1);
    }

    private function getRequestEvent(): RequestEvent
    {
        $kernel = new class() implements HttpKernelInterface {
            public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
            {
                return new Response();
            }
        };

        $request = new Request([], [], [], [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ]);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    }
}

/*
 * Hack to override the 'random_int' function
 */

namespace Setono\SyliusConsentManagementPlugin\EventListener;

use Tests\Setono\SyliusConsentManagementPlugin\EventListener\SampleCookiesSubscriberTest;

function random_int(int $min, int $max): int
{
    return SampleCookiesSubscriberTest::$randomInt;
}
