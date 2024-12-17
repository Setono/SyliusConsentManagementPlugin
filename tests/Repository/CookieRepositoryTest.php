<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepository;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Repository\CookieRepository
 */
final class CookieRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @test
     */
    public function it_finds_one_from_name(): void
    {
        $repository = new CookieRepository($this->entityManager, $this->entityManager->getClassMetadata(Cookie::class));
        $result = $repository->findOneByName('Cookie 1');

        self::assertInstanceOf(CookieInterface::class, $result);
        self::assertSame('Cookie 1', (string) $result->getName());
        self::assertSame('https://example.com', (string) $result->getUrl());
    }
}
