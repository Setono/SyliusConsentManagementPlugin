<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Repository\CookieRepository
 *
 * @property CookieRepositoryInterface $repository
 */
final class CookieRepositoryTest extends RepositoryTest
{
    protected static function getClassName(): string
    {
        return Cookie::class;
    }

    /**
     * @test
     */
    public function it_finds_one_from_name(): void
    {
        $result = $this->repository->findOneByName('Cookie 1');

        self::assertInstanceOf(CookieInterface::class, $result);
        self::assertSame('Cookie 1', (string) $result->getName());
        self::assertSame('https://example.com', (string) $result->getUrl());
    }
}
