<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;

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
        $service->setCurrentLocale('en_US');
        $service->setName('name');
        $service->setDescription('description');
        $service->setCategory(ServiceInterface::CATEGORY_PREFERENCES);

        self::assertNull($service->getId());
        self::assertSame('name', $service->getName());
        self::assertSame('description', $service->getDescription());
        self::assertSame(ServiceInterface::CATEGORY_PREFERENCES, $service->getCategory());
        self::assertEquals([
            ServiceInterface::CATEGORY_PREFERENCES => ServiceInterface::CATEGORY_PREFERENCES,
            ServiceInterface::CATEGORY_MARKETING => ServiceInterface::CATEGORY_MARKETING,
            ServiceInterface::CATEGORY_STATISTICS => ServiceInterface::CATEGORY_STATISTICS,
        ], Service::getCategories());
    }
}
