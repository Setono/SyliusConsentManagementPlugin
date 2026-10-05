<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ServiceCookieMappingTest extends KernelTestCase
{
    /**
     * @test
     */
    public function removing_a_cookie_from_a_service_does_not_delete_the_cookie(): void
    {
        $mapping = $this->getEntityManager()->getClassMetadata(Service::class)->getAssociationMapping('cookies');

        self::assertFalse($mapping['orphanRemoval'] ?? false);
    }

    /**
     * @test
     */
    public function deleting_a_service_unassigns_its_cookies(): void
    {
        $mapping = $this->getEntityManager()->getClassMetadata(Cookie::class)->getAssociationMapping('service');

        self::assertSame('SET NULL', $mapping['joinColumns'][0]['onDelete'] ?? null);
        self::assertTrue($mapping['joinColumns'][0]['nullable'] ?? true);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
