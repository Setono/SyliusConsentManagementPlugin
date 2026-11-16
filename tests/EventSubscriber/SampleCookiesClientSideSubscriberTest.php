<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesClientSideSubscriber;
use Setono\SyliusConsentManagementPlugin\Sampling\SampleTokenManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Environment;

final class SampleCookiesClientSideSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_injects_the_sampling_script_with_a_url_with_a_valid_token(): void
    {
        $tokenManager = new SampleTokenManager('secret', new ArrayAdapter());

        $sampleDecider = $this->prophesize(SampleDeciderInterface::class);
        $sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_CLIENT_SIDE)->willReturn(true);

        /** @var \ArrayObject<int, mixed> $sampleUrls */
        $sampleUrls = new \ArrayObject();
        $twig = $this->prophesize(Environment::class);
        $twig->render('@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig', Argument::type('array'))->will(
            static function (array $arguments) use ($sampleUrls): string {
                /** @var array{sample_url?: mixed} $context */
                $context = $arguments[1];
                $sampleUrls[] = $context['sample_url'] ?? null;

                return '<script>/* sample */</script>';
            },
        )->shouldBeCalledOnce();

        $response = new Response('<html><body><h1>Shop</h1></body></html>', headers: ['Content-Type' => 'text/html']);
        $response->setPublic();
        $response->setMaxAge(3600);
        $event = new ResponseEvent($this->prophesize(HttpKernelInterface::class)->reveal(), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response);

        $routes = new RouteCollection();
        $routes->add('setono_sylius_consent_management_shop_sample', new Route('/{_locale}/ajax/sample-cookies'));
        $requestContext = new RequestContext();
        $requestContext->setParameter('_locale', 'en_US');

        (new SampleCookiesClientSideSubscriber($twig->reveal(), $sampleDecider->reveal(), $tokenManager, new UrlGenerator($routes, $requestContext)))->sample($event);

        self::assertStringContainsString("<script>/* sample */</script>\n</body>", (string) $response->getContent());

        // The template gets the whole URL, so it doesn't need to know how the token is passed
        self::assertCount(1, $sampleUrls);
        self::assertIsString($sampleUrls[0]);
        self::assertSame(1, preg_match('#^/en_US/ajax/sample-cookies\?_sample_token=([^&]+)$#', $sampleUrls[0], $matches));
        self::assertTrue($tokenManager->consume(urldecode($matches[1])));

        // A cached page would give every visitor the same token, which only works once and expires
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertFalse($response->headers->hasCacheControlDirective('public'));
    }
}
