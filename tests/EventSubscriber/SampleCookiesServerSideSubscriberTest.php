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
use Setono\SyliusConsentManagementPlugin\Sampling\SamplingRateLimiter;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

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

    /** @var \ArrayObject<int, list<string|null>> */
    private \ArrayObject $recordedNames;

    private Request $request;

    private SampleCookiesServerSideSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->sampleDecider = $this->prophesize(SampleDeciderInterface::class);
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(true);

        /** @var \ArrayObject<int, list<string|null>> $recordedNames */
        $recordedNames = new \ArrayObject();
        $this->recordedNames = $recordedNames;

        $this->cookieRecorder = $this->prophesize(CookieRecorderInterface::class);
        $this->cookieRecorder->record(Argument::type('array'), Argument::type(LimiterInterface::class))->will(static function (array $arguments) use ($recordedNames): void {
            /** @var list<CookieInterface> $cookies */
            $cookies = $arguments[0];
            $recordedNames[] = array_map(static fn (CookieInterface $cookie): ?string => $cookie->getName(), $cookies);
        });

        $this->request = Request::create(uri: '/', server: ['REMOTE_ADDR' => '192.0.2.1'], cookies: [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ]);

        $this->subscriber = $this->createSubscriber(recordings: 10);
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
        $this->subscriber->sample($this->createRequestEvent());
        self::assertSame([], $this->recordedNames->getArrayCopy(), 'Nothing is saved during the request');

        $this->subscriber->record($this->createTerminateEvent());
        self::assertSame([['cookie1', 'cookie2']], $this->recordedNames->getArrayCopy());

        // The next request doesn't record the same cookies again
        $this->subscriber->record($this->createTerminateEvent());
        self::assertSame([['cookie1', 'cookie2']], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_reads_the_real_cookie_names_from_the_cookie_header(): void
    {
        // PHP would have turned these into 'ai_session_v1', 'my_cookie' and 'x' in $_COOKIE
        $this->request = Request::create('/', server: ['HTTP_COOKIE' => 'ai_session.v1=a; my cookie=b; x[y]=c; _ga=GA1.1.1; _ga=duplicate', 'REMOTE_ADDR' => '192.0.2.1']);
        $this->request->cookies->replace(['ai_session_v1' => 'a', 'my_cookie' => 'b', 'x' => ['y' => 'c'], '_ga' => 'GA1.1.1']);

        $this->subscriber->sample($this->createRequestEvent());
        $this->subscriber->record($this->createTerminateEvent());

        // The recorder skips the names that aren't valid cookie names and records '_ga' once, see CookieRecorderTest
        self::assertSame([['ai_session.v1', 'my cookie', 'x[y]', '_ga', '_ga']], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_skips_cookie_header_parts_without_a_name(): void
    {
        // A browser sends a cookie without a name as just its value. '=value' has an empty name, which the recorder
        // skips like any invalid name
        $this->request = Request::create('/', server: ['HTTP_COOKIE' => 'abc123; _ga=GA1.1.1; =value', 'REMOTE_ADDR' => '192.0.2.1']);

        $this->subscriber->sample($this->createRequestEvent());
        $this->subscriber->record($this->createTerminateEvent());

        self::assertSame([['_ga', '']], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_does_not_sample_on_sub_request(): void
    {
        $this->subscriber->sample($this->createRequestEvent(HttpKernelInterface::SUB_REQUEST));
        $this->subscriber->record($this->createTerminateEvent());

        self::assertSame([], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_sample_decider_says_no(): void
    {
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(false);

        $this->subscriber->sample($this->createRequestEvent());
        $this->subscriber->record($this->createTerminateEvent());

        self::assertSame([], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_forgets_the_sampled_cookies_on_reset(): void
    {
        $this->subscriber->sample($this->createRequestEvent());
        $this->subscriber->reset();
        $this->subscriber->record($this->createTerminateEvent());

        self::assertSame([], $this->recordedNames->getArrayCopy());
    }

    /**
     * @test
     */
    public function it_silently_skips_a_client_that_has_been_recorded_too_often(): void
    {
        $subscriber = $this->createSubscriber(recordings: 2);

        foreach (range(1, 3) as $_) {
            $subscriber->sample($this->createRequestEvent());
            $subscriber->record($this->createTerminateEvent());
        }

        self::assertSame([['cookie1', 'cookie2'], ['cookie1', 'cookie2']], $this->recordedNames->getArrayCopy());

        // Another client
        $this->request->server->set('REMOTE_ADDR', '192.0.2.2');
        $subscriber->sample($this->createRequestEvent());
        $subscriber->record($this->createTerminateEvent());

        self::assertCount(3, $this->recordedNames);
    }

    /**
     * @test
     */
    public function it_does_not_count_requests_that_were_not_sampled(): void
    {
        $this->sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_SERVER_SIDE)->willReturn(false, false, true);
        $subscriber = $this->createSubscriber(recordings: 1);

        foreach (range(1, 3) as $_) {
            $subscriber->sample($this->createRequestEvent());
            $subscriber->record($this->createTerminateEvent());
        }

        self::assertSame([['cookie1', 'cookie2']], $this->recordedNames->getArrayCopy());
    }

    private function createSubscriber(int $recordings): SampleCookiesServerSideSubscriber
    {
        return new SampleCookiesServerSideSubscriber(
            $this->cookieRecorder->reveal(),
            new CookieFactory(new Factory(Cookie::class), new CompositeChannelContext(), new RequestStack()),
            $this->sampleDecider->reveal(),
            new SamplingRateLimiter(
                new RateLimiterFactory(['id' => 'recordings', 'policy' => 'sliding_window', 'limit' => $recordings, 'interval' => '1 hour'], new InMemoryStorage()),
                new RateLimiterFactory(['id' => 'new_cookies', 'policy' => 'sliding_window', 'limit' => 10, 'interval' => '1 day'], new InMemoryStorage()),
            ),
        );
    }

    private function createRequestEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $this->request, $requestType);
    }

    private function createTerminateEvent(): TerminateEvent
    {
        return new TerminateEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $this->request, new Response());
    }
}
