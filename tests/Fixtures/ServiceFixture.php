<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Setono\Consent\Consent;
use Setono\SyliusConsentManagementPlugin\Model\Service;

final class ServiceFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::services() as $data) {
            $service = new Service();
            $service->setCurrentLocale('en_US');
            $service->setCode(sprintf('service_%s', $data[0]));
            $service->setName(sprintf('Service %s', $data[0]));
            $service->setCategory($data[1]);
            $service->setDescription($data[2]);
            $service->setCreatedAt(new \DateTime());

            $manager->persist($service);
        }

        $manager->flush();
    }

    /**
     * @return iterable<array<string>>
     */
    private static function services(): iterable
    {
        $i = 0;

        yield [(string) ++$i, Consent::CONSENT_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_PREFERENCES, 'Very important service'];

        yield [(string) ++$i, Consent::CONSENT_STATISTICS, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_STATISTICS, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_STATISTICS, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_STATISTICS, 'Very important service'];

        yield [(string) ++$i, Consent::CONSENT_MARKETING, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_MARKETING, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_MARKETING, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_MARKETING, 'Very important service'];
        yield [(string) ++$i, Consent::CONSENT_MARKETING, 'Very important service'];
    }
}
