<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\Service;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\Cookie
 */
class CookieTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_and_gets(): void
    {
        $service = new Service();

        $cookie = new Cookie();
        $cookie->setName('name');
        $cookie->setUrl('https://example.com');
        $cookie->setService($service);
        $cookie->setNecessary(true);

        self::assertNull($cookie->getId());
        self::assertSame('name', $cookie->getName());
        self::assertSame('https://example.com', $cookie->getUrl());
        self::assertSame($service, $cookie->getService());
        self::assertTrue($cookie->isNecessary());
    }

    /**
     * @test
     */
    public function it_sets_service_to_null_if_cookie_is_necessary(): void
    {
        $service = new Service();

        $cookie = new Cookie();
        $cookie->setService($service);
        $cookie->setNecessary(true);
        $cookie->preUpdate();

        self::assertNull($cookie->getService());
    }
}
