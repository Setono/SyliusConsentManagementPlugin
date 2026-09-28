<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Controller\SampleController;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
     * @param list<array<string, mixed>> $samples
     */
    private static function createRequest(array $samples): Request
    {
        // On Sylius 1.x FOSRestBundle's body listener decodes the JSON body into the request parameters
        return Request::create('/en_US/ajax/sample-cookies', 'POST', $samples);
    }
}
