<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tests\Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Fixture\TestUrlProvider;

final class AbstractUrlProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     *
     * @dataProvider provideRequestContexts
     */
    public function it_generates_urls_for_channels_with_a_hostname(RequestContext $context): void
    {
        $originalContext = clone $context;

        self::assertSame(
            'https://shop.example.com/en_US/products/mug?gclid=test',
            $this->createProvider($context)->generate($this->createChannel('shop.example.com'), 'en_US', 'sylius_shop_product_show', ['slug' => 'mug', 'gclid' => 'test']),
        );
        self::assertEquals($originalContext, $context, 'The request context was not restored');
    }

    /**
     * @return iterable<string, array{RequestContext}>
     */
    public static function provideRequestContexts(): iterable
    {
        yield 'http' => [RequestContext::fromUri('http://localhost')];
        yield 'http with a port' => [RequestContext::fromUri('http://localhost:8000')];
        yield 'https with a port' => [RequestContext::fromUri('https://localhost:8443')];
    }

    /**
     * @test
     */
    public function it_generates_urls_for_channels_with_a_hostname_when_the_route_requires_https(): void
    {
        self::assertSame(
            'https://shop.example.com/en_US/',
            $this->createProvider(RequestContext::fromUri('http://localhost'))->generate($this->createChannel('shop.example.com'), 'en_US', 'https_homepage'),
        );
    }

    /**
     * @test
     */
    public function it_restores_the_request_context_when_the_url_cannot_be_generated(): void
    {
        $context = RequestContext::fromUri('http://localhost');

        try {
            $this->createProvider($context)->generate($this->createChannel('shop.example.com'), 'en_US', 'sylius_shop_product_show');

            self::fail('The URL was generated without a slug');
        } catch (MissingMandatoryParametersException) {
            // The slug is missing
        }

        self::assertEquals(RequestContext::fromUri('http://localhost'), $context);
    }

    /**
     * @test
     */
    public function it_generates_urls_from_the_request_context_for_channels_without_a_hostname(): void
    {
        self::assertSame(
            'http://localhost:8000/en_US/',
            $this->createProvider(RequestContext::fromUri('http://localhost:8000'))->generate($this->createChannel(null), 'en_US', 'sylius_shop_homepage'),
        );
    }

    private function createProvider(RequestContext $context): TestUrlProvider
    {
        $routes = new RouteCollection();
        $routes->add('sylius_shop_homepage', new Route('/{_locale}/'));
        $routes->add('sylius_shop_product_show', new Route('/{_locale}/products/{slug}'));
        $routes->add('https_homepage', new Route('/{_locale}/', schemes: ['https']));

        return new TestUrlProvider($this->prophesize(ManagerRegistry::class)->reveal(), new UrlGenerator($routes, $context));
    }

    private function createChannel(?string $hostname): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getHostname()->willReturn($hostname);

        return $channel->reveal();
    }
}
