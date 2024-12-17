<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepository;
use Tests\Setono\SyliusConsentManagementPlugin\Repository\AbstractRepositoryTest;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Repository\ServiceRepository
 */
final class ServiceRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @test
     */
    public function it_finds_all_indexed_by_category(): void
    {
        $repository = new ServiceRepository($this->entityManager, $this->entityManager->getClassMetadata(Service::class));
        $result = $repository->findAllIndexedByCategory();

        self::assertArrayHasKey('preferences', $result);
        self::assertCount(3, $result['preferences']);

        self::assertArrayHasKey('statistics', $result);
        self::assertCount(4, $result['statistics']);

        self::assertArrayHasKey('marketing', $result);
        self::assertCount(5, $result['marketing']);

        foreach ($result as $services) {
            foreach ($services as $service) {
                self::assertInstanceOf(Service::class, $service);
                self::assertInstanceOf(ServiceInterface::class, $service);
            }
        }
    }
}
