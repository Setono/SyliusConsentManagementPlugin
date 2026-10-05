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
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber
 */
final class SampleCookiesServerSideSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CookieRecorderInterface> */
    private ObjectProphecy $cookieRecorder;

    /** @var ObjectProphecy<SampleDeciderInterface> */
    private ObjectProphecy $sampleDecider;

    /** @var ObjectProphecy<RequestEvent> */
    private ObjectProphecy $requestEvent;

    private SampleCookiesServerSideSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cookieRecorder = $this->prophesize(CookieRecorderInterface::class);
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
            $this->cookieRecorder->reveal(),
            new CookieFactory(new Factory(Cookie::class), new CompositeChannelContext(), new RequestStack()),
            $this->sampleDecider->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_subscribes(): void
    {
        self::assertSame([
            KernelEvents::REQUEST => 'sample',
            KernelEvents::TERMINATE => ['record', 10],
        ], SampleCookiesServerSideSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_records_the_sampled_cookies_when_the_kernel_terminates(): void
    {
        /** @var \ArrayObject<int, list<string|null>> $recordedNames */
        $recordedNames = new \ArrayObject();
        $this->cookieRecorder->record(Argument::type('array'))->will(static function (array $arguments) use ($recordedNames): void {
            /** @var array<string, CookieInterface> $cookies */
            $cookies = $arguments[0];
            $recordedNames[] = array_map(static fn (CookieInterface $cookie): ?string => $cookie->getName(), array_values($cookies));
        });

        $this->subscriber->sample($this->requestEvent->reveal());
        self::assertSame([], $recordedNames->getArrayCopy(), 'Nothing is saved during the request');

        $this->subscriber->record();
        self::assertSame([['cookie1', 'cookie2']], $recordedNames->getArrayCopy());

        // The next request doesn't record the same cookies again
        $this->subscriber->record();
        self::assertSame([['cookie1', 'cookie2'], []], $recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_reads_the_real_cookie_names_from_the_cookie_header(): void
    {
        // PHP would have turned these into 'ai_session_v1', 'my_cookie' and 'x' in $_COOKIE
        $request = Request::create('/', server: ['HTTP_COOKIE' => 'ai_session.v1=a; my cookie=b; x[y]=c; _ga=GA1.1.1; _ga=duplicate']);
        $request->cookies->replace(['ai_session_v1' => 'a', 'my_cookie' => 'b', 'x' => ['y' => 'c'], '_ga' => 'GA1.1.1']);
        $this->requestEvent->getRequest()->willReturn($request);

        $this->cookieRecorder->record(Argument::that(
            static fn (array $cookies): bool => ['ai_session.v1', 'my cookie', 'x[y]', '_ga'] === array_keys($cookies),
        ))->shouldBeCalledOnce();

        $this->subscriber->sample($this->requestEvent->reveal());
        $this->subscriber->record();
    }

    /**
     * @test
     */
    public function it_does_not_sample_on_sub_request(): void
    {
        $this->requestEvent->isMainRequest()->willReturn(false);
        $this->requestEvent->getRequest()->shouldNotBeCalled();

        $this->subscriber->sample($this->requestEvent->reveal());

        $this->cookieRecorder->record([])->shouldBeCalledOnce();
        $this->subscriber->record();
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_sample_decider_says_no(): void
    {
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(false);

        $this->subscriber->sample($this->requestEvent->reveal());

        $this->cookieRecorder->record([])->shouldBeCalledOnce();
        $this->subscriber->record();
    }

    /**
     * @test
     */
    public function it_forgets_the_sampled_cookies_on_reset(): void
    {
        $this->subscriber->sample($this->requestEvent->reveal());
        $this->subscriber->reset();

        $this->cookieRecorder->record([])->shouldBeCalledOnce();
        $this->subscriber->record();
    }
}
