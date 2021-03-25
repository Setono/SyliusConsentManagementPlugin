<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use PHPUnit\Framework\TestCase;
use Setono\ClientIdBundle\Doctrine\Type\ClientIdType;
use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ServiceRepository;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ServiceRepository
 */
final class ServiceRepositoryTest extends TestCase
{
    private bool $databaseCreated = false;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        if (!$this->databaseCreated) {
            if (!Type::hasType('client_id')) {
                Type::addType('client_id', ClientIdType::class);
            }

            $fileLocator = new SymfonyFileLocator([
                __DIR__ . '/../../../src/Resources/config/doctrine/model' => 'Setono\SyliusConsentManagementPlugin\Model',
            ], '.orm.xml');
            $config = Setup::createXMLMetadataConfiguration([], true);
            $config->setMetadataDriverImpl(new XmlDriver($fileLocator));

            $this->entityManager = EntityManager::create([
                'driver' => 'pdo_sqlite',
                'path' => __DIR__ . '/db.sqlite',
            ], $config);

            $classes = [
                $this->entityManager->getClassMetadata(ConsentEntry::class),
                $this->entityManager->getClassMetadata(Service::class),
            ];

            foreach ($classes as $class) {
                $class->isMappedSuperclass = false;
            }

            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->dropSchema($classes);
            $schemaTool->createSchema($classes);

            $loader = new Loader();
            $loader->loadFromDirectory(__DIR__ . '/../../Fixtures');

            $executor = new ORMExecutor($this->entityManager, new ORMPurger());
            $executor->execute($loader->getFixtures());
        }
    }

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
