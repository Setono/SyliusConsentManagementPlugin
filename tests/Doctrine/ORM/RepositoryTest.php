<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

abstract class RepositoryTest extends KernelTestCase
{
    protected ObjectManager $entityManager;

    protected ObjectRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManagerForClass(static::getClassName())
        ;
        Assert::isInstanceOf($manager, EntityManagerInterface::class);
        $this->entityManager = $manager;

        $this->repository = $this->entityManager->getRepository(static::getClassName());

        $loader = new Loader();
        $loader->loadFromDirectory(__DIR__ . '/../../Fixtures');

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute($loader->getFixtures());
    }

    /**
     * @return class-string
     */
    abstract protected static function getClassName(): string;
}
