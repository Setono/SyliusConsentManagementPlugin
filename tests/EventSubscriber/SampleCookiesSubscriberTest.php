<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusConsentManagementPlugin\Event\CookiesCreatedEvent;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent as BaseRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesSubscriber
 */
final class SampleCookiesSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public static int $randomInt = 0;

    protected function setUp(): void
    {
        self::$randomInt = 0;
    }

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

    /**
     * @test
     */
    public function it_does_not_sample_on_sub_request(): void
    {
        $event = $this->getRequestEvent(HttpKernelInterface::SUB_REQUEST);

        $subscriber = $this->getSubscriber(false, false);
        $subscriber->sample($event);

        self::assertFalse($event->getRequestCalled);
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_sample_rate_is_not_met(): void
    {
        self::$randomInt = 10000;

        $event = $this->getRequestEvent();

        $subscriber = $this->getSubscriber(false, false, 0.5);
        $subscriber->sample($event);

        self::assertTrue($event->getRequestCalled);
    }

    /**
     * @test
     */
    public function it_samples_if_sample_rate_is_met_and_firewall_constraint_is_met(): void
    {
        $event = $this->getRequestEvent();

        $subscriber = $this->getSubscriber(true, true, 1, true);
        $subscriber->sample($event);

        self::assertTrue($event->getRequestCalled);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_sample_rate_is_out_of_upper_bound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getSubscriber(false, false, 1.1);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_sample_rate_is_out_of_lower_bound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getSubscriber(false, false, 0.00001);
    }

    /**
     * @param array<array-key, string> $firewalls
     */
    private function getSubscriber(
        bool $callRepository = true,
        bool $callEventDispatcher = true,
        float $sampleRate = 1,
        bool $callFirewallConfig = false,
        array $firewalls = ['shop'],
    ): SampleCookiesSubscriber {
        $repository = $this->prophesize(CookieRepositoryInterface::class);
        if ($callRepository) {
            $repository->findOneByName(Argument::type('string'))->willReturn(null, new Cookie());
            $repository->add(Argument::type(Cookie::class))->shouldBeCalledOnce();
        }

        $factory = new CookieFactory(new Factory(Cookie::class));

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        if ($callEventDispatcher) {
            $eventDispatcher->dispatch(Argument::type(CookiesCreatedEvent::class))->shouldBeCalled();
        } else {
            $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        }

        $firewallMap = $this->prophesize(FirewallMap::class);
        if ($callFirewallConfig) {
            $firewallConfig = new FirewallConfig('shop', 'user_checker');
            $firewallMap->getFirewallConfig(Argument::type(Request::class))->willReturn($firewallConfig);
        }

        return new SampleCookiesSubscriber($repository->reveal(), $factory, $eventDispatcher->reveal(), $firewallMap->reveal(), $firewalls, $sampleRate);
    }

    private function getRequestEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = new class() implements HttpKernelInterface {
            public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
            {
                return new Response();
            }
        };

        $request = new Request([], [], [], [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ]);

        return new RequestEvent($kernel, $request, $requestType);
    }
}

class RequestEvent extends BaseRequestEvent
{
    public bool $getRequestCalled = false;

    public function getRequest(): Request
    {
        $this->getRequestCalled = true;

        return parent::getRequest();
    }
}

/*
 * Hack to override the 'random_int' function
 */

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesSubscriberTest;

function random_int(int $min, int $max): int
{
    return SampleCookiesSubscriberTest::$randomInt;
}
