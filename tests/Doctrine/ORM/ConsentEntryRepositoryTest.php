<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepository
 *
 * @property ConsentEntryRepositoryInterface $repository
 */
final class ConsentEntryRepositoryTest extends RepositoryTest
{
    protected static function getClassName(): string
    {
        return ConsentEntry::class;
    }

    /**
     * @test
     */
    public function it_finds_consent_from_client_id(): void
    {
        $result = $this->repository->findConsentFromClientId(new ClientId('client_id_1'));

        self::assertInstanceOf(Consent::class, $result);
        self::assertSame('client_id_1', $result->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_finds_one_from_client_id(): void
    {
        $result = $this->repository->findOneFromClientId(new ClientId('client_id_1'));

        self::assertInstanceOf(ConsentEntryInterface::class, $result);
        self::assertSame('client_id_1', (string) $result->getClientId());
    }
}
