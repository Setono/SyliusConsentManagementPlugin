<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;

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

        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];

        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];

        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
    }
}
