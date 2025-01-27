<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Repository;

use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepository;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepository
 */
final class ConsentEntryRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @test
     */
    public function it_finds_consent_from_client_id(): void
    {
        $repository = new ConsentEntryRepository($this->entityManager, $this->entityManager->getClassMetadata(ConsentEntry::class));
        $result = $repository->findFromClient(new ClientId('client_id_1'));

        self::assertInstanceOf(Consent::class, $result);
        self::assertSame('client_id_1', $result->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_finds_one_from_client_id(): void
    {
        $repository = new ConsentEntryRepository($this->entityManager, $this->entityManager->getClassMetadata(ConsentEntry::class));
        $result = $repository->findOneFromClient(new ClientId('client_id_1'));

        self::assertInstanceOf(ConsentEntryInterface::class, $result);
        self::assertSame('client_id_1', (string) $result->getClientId());
    }
}
