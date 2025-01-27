<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\Service;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\Service
 */
final class ServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_and_sets(): void
    {
        $service = new Service();
        self::assertCount(0, $service->getCookies());

        $service->setCurrentLocale('en_US');
        $service->setName('name');
        $service->setDescription('description');

        $service->setCode('code');

        $category = new Category();
        $service->setCategory($category);

        $cookie = new Cookie();
        $service->addCookie($cookie);

        self::assertNull($service->getId());
        self::assertSame('name', $service->getName());
        self::assertSame('description', $service->getDescription());

        self::assertSame('code', $service->getCode());
        self::assertSame($category, $service->getCategory());
        self::assertCount(1, $service->getCookies());
        self::assertTrue($service->hasCookie($cookie));
        self::assertSame($service, $cookie->getService());
    }

    /**
     * @test
     */
    public function it_removes_cookie(): void
    {
        $cookie = new Cookie();

        $service = new Service();
        $service->addCookie($cookie);

        self::assertCount(1, $service->getCookies());

        $service->removeCookie($cookie);
        self::assertCount(0, $service->getCookies());
        self::assertNull($cookie->getService());
    }

    /**
     * @test
     */
    public function it_is_stringable(): void
    {
        $service = new Service();
        $service->setCurrentLocale('en_US');

        self::assertSame('', (string) $service);
    }

    /**
     * @test
     */
    public function it_is_extendable(): void
    {
        $service = new class() extends Service {
            public function __construct()
            {
                parent::__construct();

                $this->setCurrentLocale('en_US');
                $translation = $this->createTranslation();
                $translation->setName('name');
                $this->addTranslation($translation);
            }
        };

        self::assertSame('name', $service->getName());
    }
}
