<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Repository;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

abstract class AbstractRepositoryTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @psalm-suppress MixedMethodCall,PossiblyNullReference */
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        Assert::isInstanceOf($entityManager, EntityManagerInterface::class);

        $this->entityManager = $entityManager;

        $loader = new Loader();
        $loader->loadFromDirectory(__DIR__ . '/../../Fixtures');

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute($loader->getFixtures());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
    }
}
