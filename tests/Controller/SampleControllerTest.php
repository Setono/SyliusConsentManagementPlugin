<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Controller\SampleController;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManager;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManagerInterface;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SampleControllerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CookieRecorderInterface> */
    private ObjectProphecy $cookieRecorder;

    private SampleController $controller;

    protected function setUp(): void
    {
        $this->cookieRecorder = $this->prophesize(CookieRecorderInterface::class);

        $this->controller = new SampleController(
            $this->cookieRecorder->reveal(),
            new CookieFactory(new Factory(Cookie::class), new CompositeChannelContext(), new RequestStack()),
            new SampleTokenManager('secret'),
        );
    }

    /**
     * @test
     */
    public function it_records_the_sampled_cookies_once_per_name(): void
    {
        $this->cookieRecorder->record(Argument::that(static function (array $cookies): bool {
            /** @var array<string, CookieInterface> $cookies */
            $names = array_map(static fn (CookieInterface $cookie): ?string => $cookie->getName(), array_values($cookies));

            return ['_ga', '_fbp'] === array_keys($cookies) && ['_ga', '_fbp'] === $names;
        }))->shouldBeCalledOnce();

        $response = ($this->controller)(self::createRequest([
            ['name' => '_ga', 'expires' => null],
            ['name' => '_fbp', 'expires' => 1790000000000],
            ['name' => '_ga', 'expires' => null],
        ]));

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_samples(): void
    {
        $this->cookieRecorder->record(Argument::any())->shouldNotBeCalled();

        $this->expectException(BadRequestHttpException::class);

        ($this->controller)(self::createRequest([['name' => '_ga', 'expires' => 'tomorrow']]));
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidTokens
     */
    public function it_rejects_samples_without_a_valid_token(?string $token): void
    {
        $this->cookieRecorder->record(Argument::any())->shouldNotBeCalled();

        $this->expectException(AccessDeniedHttpException::class);

        ($this->controller)(self::createRequestWithBody('[{"name": "_ga", "expires": null}]', $token));
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function provideInvalidTokens(): iterable
    {
        yield 'missing' => [null];
        yield 'another secret' => [(new SampleTokenManager('another secret'))->create()];
        yield 'expired' => [(new SampleTokenManager('secret', -1))->create()];
    }

    /**
     * @test
     */
    public function it_skips_names_that_cannot_be_cookie_names(): void
    {
        $this->cookieRecorder->record(Argument::that(static fn (array $cookies): bool => ['_ga'] === array_keys($cookies)))->shouldBeCalledOnce();

        ($this->controller)(self::createRequest([
            ['name' => '_ga', 'expires' => null],
            ['name' => 'VISIT evil.example TO VERIFY YOUR STORE', 'expires' => null],
            ['name' => '<script>', 'expires' => null],
        ]));
    }

    /**
     * @test
     */
    public function it_rejects_too_many_cookies(): void
    {
        $this->cookieRecorder->record(Argument::any())->shouldNotBeCalled();

        $this->expectException(BadRequestHttpException::class);

        ($this->controller)(self::createRequest(array_map(
            static fn (int $i): array => ['name' => 'cookie' . $i, 'expires' => null],
            range(1, SampleController::MAX_COOKIES + 1),
        )));
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidBodies
     */
    public function it_rejects_bodies_that_are_not_a_json_list(string $body): void
    {
        $this->cookieRecorder->record(Argument::any())->shouldNotBeCalled();

        $this->expectException(BadRequestHttpException::class);

        ($this->controller)(self::createRequestWithBody($body));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidBodies(): iterable
    {
        yield 'empty' => [''];
        yield 'malformed' => ['[{"name": "_ga"'];
        yield 'object' => ['{"name": "_ga", "expires": null}'];
    }

    /**
     * @param list<array<string, mixed>> $samples
     */
    private static function createRequest(array $samples): Request
    {
        return self::createRequestWithBody(json_encode($samples, \JSON_THROW_ON_ERROR));
    }

    /**
     * Like the sampling script, and without any listener decoding the body into the request parameters
     */
    private static function createRequestWithBody(string $body, ?string $token = 'valid'): Request
    {
        $query = [];
        if (null !== $token) {
            $query[SampleTokenManagerInterface::QUERY_PARAMETER] = 'valid' === $token ? (new SampleTokenManager('secret'))->create() : $token;
        }

        return Request::create('/en_US/ajax/sample-cookies?' . http_build_query($query), 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: $body);
    }
}
