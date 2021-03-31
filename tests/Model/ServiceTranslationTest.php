<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation
 */
final class ServiceTranslationTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_and_sets(): void
    {
        $serviceTranslation = new ServiceTranslation();
        $serviceTranslation->setName('name');
        $serviceTranslation->setDescription('description');

        self::assertNull($serviceTranslation->getId());
        self::assertSame('name', $serviceTranslation->getName());
        self::assertSame('description', $serviceTranslation->getDescription());
    }
}
