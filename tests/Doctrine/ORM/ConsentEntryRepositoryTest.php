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
use Setono\ClientId\ClientId;
use Setono\ClientIdBundle\Doctrine\Type\ClientIdType;
use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ConsentEntryRepository;
use Setono\SyliusConsentManagementPlugin\Model\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ConsentEntryRepository
 */
final class ConsentEntryRepositoryTest extends TestCase
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
    public function it_finds_consent_from_client_id(): void
    {
        $repository = new ConsentEntryRepository($this->entityManager, $this->entityManager->getClassMetadata(ConsentEntry::class));
        $result = $repository->findConsentFromClientId(new ClientId('client_id_1'));

        self::assertInstanceOf(Consent::class, $result);
        self::assertSame('client_id_1', $result->getClientId()->toString());
    }
}
