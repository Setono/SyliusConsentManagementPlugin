<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;

final class ConsentEntryFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::consentEntries() as $data) {
            $consentEntry = new ConsentEntry();
            $consentEntry->setClientId(new ClientId($data[0]));
            $consentEntry->setIp('213.98.45.12');
            $consentEntry->setUrl('https://example.com');
            $consentEntry->setUserAgent('Bad user agent');
            $consentEntry->setCreatedAt(new \DateTime());

            $manager->persist($consentEntry);
        }

        $manager->flush();
    }

    /**
     * @return iterable<array<string>>
     */
    private static function consentEntries(): iterable
    {
        $i = 0;

        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
        yield ['client_id_' . ++$i];
    }
}
