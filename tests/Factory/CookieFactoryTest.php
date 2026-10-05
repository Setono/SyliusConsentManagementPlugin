<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\CookieFactory
 */
final class CookieFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_with_name(): void
    {
        $factory = self::getFactory();
        $cookie = $factory->createWithName('name');

        self::assertSame('name', $cookie->getName());
    }

    /**
     * @test
     */
    public function it_stores_the_request_url_without_query_string(): void
    {
        $request = Request::create('https://shop.example.com:8443/en_US/products/mug?fbclid=' . str_repeat('x', 300));

        $cookie = self::getFactory($request)->createWithName('_fbp');

        self::assertSame('https://shop.example.com:8443/en_US/products/mug', $cookie->getUrl());
    }

    /**
     * @test
     */
    public function it_truncates_a_long_request_url_to_the_column_length(): void
    {
        $url = 'https://shop.example.com/en_US/products/' . str_repeat('x', 300);

        $cookie = self::getFactory(Request::create($url))->createWithName('_fbp');

        self::assertSame(substr($url, 0, 255), $cookie->getUrl());
    }

    /**
     * @test
     */
    public function it_uses_the_referer_for_ajax_requests(): void
    {
        $request = Request::create('https://shop.example.com/en_US/ajax/sample-cookies', 'POST', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'https://shop.example.com/en_US/?gclid=abc',
        ]);

        $cookie = self::getFactory($request)->createWithName('_ga');

        self::assertSame('https://shop.example.com/en_US/', $cookie->getUrl());
    }

    /**
     * @test
     */
    public function it_ignores_referers_from_another_host_for_ajax_requests(): void
    {
        $request = Request::create('https://shop.example.com/en_US/ajax/sample-cookies', 'POST', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'https://evil.example/admin-login',
        ]);

        $cookie = self::getFactory($request)->createWithName('_ga');

        self::assertNull($cookie->getUrl());
    }

    /**
     * @test
     */
    public function it_ignores_non_http_referers(): void
    {
        $request = Request::create('https://shop.example.com/en_US/ajax/sample-cookies', 'POST', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'javascript:alert(document.domain)',
        ]);

        $cookie = self::getFactory($request)->createWithName('_ga');

        self::assertNull($cookie->getUrl());
    }

    /**
     * @test
     */
    public function it_keeps_the_query_string_of_crawled_urls_but_truncates_them(): void
    {
        $url = 'https://shop.example.com/en_US/products/mug?ttclid=' . str_repeat('x', 300) . '&_consent=1&_sample=0';

        $cookie = self::getFactory()->createFromBrowserKitCookie(new BrowserKitCookie('_ttp', 'value'), new Url($url));

        self::assertSame(substr($url, 0, 255), $cookie->getUrl());
    }

    private static function getFactory(?Request $request = null): CookieFactoryInterface
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return new CookieFactory(
            new Factory(Cookie::class),
            new CompositeChannelContext(),
            $requestStack,
        );
    }
}
