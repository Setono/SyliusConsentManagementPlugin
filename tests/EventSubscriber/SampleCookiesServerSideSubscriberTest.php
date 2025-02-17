<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber
 */
final class SampleCookiesServerSideSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CookieRepositoryInterface> */
    private ObjectProphecy $cookieRepository;

    /** @var ObjectProphecy<SampleDeciderInterface> */
    private ObjectProphecy $sampleDecider;

    /** @var ObjectProphecy<RequestEvent> */
    private ObjectProphecy $requestEvent;

    private SampleCookiesServerSideSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cookieRepository = $this->prophesize(CookieRepositoryInterface::class);
        $this->sampleDecider = $this->prophesize(SampleDeciderInterface::class);
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(true);

        $request = Request::create(uri: '/', cookies: [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ]);

        $this->requestEvent = $this->prophesize(RequestEvent::class);
        $this->requestEvent->isMainRequest()->willReturn(true);
        $this->requestEvent->getRequest()->willReturn($request);

        $this->subscriber = new SampleCookiesServerSideSubscriber(
            $this->cookieRepository->reveal(),
            new CookieFactory(new Factory(Cookie::class)),
            $this->sampleDecider->reveal(),
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
    public function it_samples(): void
    {
        $this->cookieRepository->findOneByName(Argument::type('string'))->willReturn(new Cookie());
        $this->cookieRepository->add(Argument::type(Cookie::class))->shouldBeCalledTimes(2);

        $this->subscriber->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_sample_on_sub_request(): void
    {
        $this->requestEvent->isMainRequest()->willReturn(false);
        $this->requestEvent->getRequest()->shouldNotBeCalled();

        $this->subscriber->sample($this->requestEvent->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_sample_decider_says_no(): void
    {
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(false);

        $this->requestEvent->getRequest()->shouldBeCalled();

        $this->cookieRepository->findOneByName(Argument::any())->shouldNotBeCalled();

        $this->subscriber->sample($this->requestEvent->reveal());
    }
}
