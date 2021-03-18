<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ServiceRepository;
use Setono\SyliusConsentManagementPlugin\Model\Service;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ServiceRepository
 */
final class ServiceRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_finds_all_indexed_by_category(): void
    {
        $fileLocator = new SymfonyFileLocator([
            __DIR__ . '/../../../src/Resources/config/doctrine/model' => 'Setono\SyliusConsentManagementPlugin\Model',
        ], '.orm.xml');
        $config = Setup::createXMLMetadataConfiguration([], true);
        $config->setMetadataDriverImpl(new XmlDriver($fileLocator));
        $conn = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        ];

        $entityManager = EntityManager::create($conn, $config);
        $repository = new ServiceRepository($entityManager, $entityManager->getClassMetadata(Service::class));
        $repository->findAllIndexedByCategory();
    }
}
