<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Sylius\Resource\Factory\Factory;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber
 */
final class SampleCookiesServerSideSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public static int $randomInt = 0;

    /** @var ObjectProphecy<CookieRepositoryInterface> */
    private ObjectProphecy $cookieRepository;

    /** @var ObjectProphecy<FirewallMap> */
    private ObjectProphecy $firewallMap;

    /** @var ObjectProphecy<RequestEvent> */
    private ObjectProphecy $requestEvent;

    protected function setUp(): void
    {
        self::$randomInt = 0;

        $this->cookieRepository = $this->prophesize(CookieRepositoryInterface::class);
        $this->firewallMap = $this->prophesize(FirewallMap::class);

        $request = Request::create(uri: '/', cookies: [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ]);

        $this->requestEvent = $this->prophesize(RequestEvent::class);
        $this->requestEvent->getRequest()->willReturn($request);
    }

    /**
     * The reason we don't initialize the subscriber directly inside the setUp method above is because we need to be able to change $sampleRate
     */
    private function createSubscriber(float $sampleRate = 1, FirewallMapInterface $firewallMap = null): SampleCookiesServerSideSubscriber
    {
        $firewallMap ??= $this->firewallMap->reveal();

        return new SampleCookiesServerSideSubscriber(
            $this->cookieRepository->reveal(),
            new CookieFactory(new Factory(Cookie::class)),
            $firewallMap,
            ['shop'],
            $sampleRate,
        );
    }

    /**
     * @test
     */
    public function it_subscribes(): void
    {
        self::assertSame([KernelEvents::REQUEST => 'sample'], SampleCookiesServerSideSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_is_not_expected_instance(): void
    {
        $this->requestEvent->isMainRequest()->willReturn(true);

        $this->cookieRepository->findOneByName(Argument::type('string'))->willReturn(new Cookie());
        $this->cookieRepository->add(Argument::type(Cookie::class))->shouldBeCalledTimes(2);

        $this->createSubscriber(firewallMap: $this->prophesize(FirewallMapInterface::class)->reveal())->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_is_expected_instance(): void
    {
        $this->createSubscriber()->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_config_constraint_is_met(): void
    {
        $this->firewallMap
            ->getFirewallConfig(Argument::type(Request::class))
            ->willReturn(new FirewallConfig('shop', 'user_checker'))
            ->shouldBeCalledOnce()
        ;

        $this->createSubscriber()->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_sample_on_sub_request(): void
    {
        $this->requestEvent->isMainRequest()->willReturn(false);
        $this->requestEvent->getRequest()->shouldNotBeCalled();

        $this->createSubscriber()->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_sample_rate_is_not_met(): void
    {
        self::$randomInt = mt_getrandmax();

        $this->requestEvent->isMainRequest()->willReturn(true);
        $this->requestEvent->getRequest()->shouldBeCalled();

        $this->cookieRepository->findOneByName(Argument::any())->shouldNotBeCalled();

        $this->createSubscriber(0.5)->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_sample_rate_is_out_of_upper_bound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createSubscriber(1.1);
    }
}

/*
 * Hack to override the 'random_int' function
 */

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriberTest;

function random_int(int $min, int $max): int
{
    return SampleCookiesServerSideSubscriberTest::$randomInt;
}
