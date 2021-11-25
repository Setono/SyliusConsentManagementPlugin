<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;

final class CookieFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::cookies() as $data) {
            $cookie = new Cookie();
            $cookie->setName($data[0]);
            $cookie->setExampleValue($data[1]);
            $cookie->setUrl($data[2]);
            $cookie->setCreatedAt(new \DateTime());

            $manager->persist($cookie);
        }

        $manager->flush();
    }

    /**
     * @return iterable<array<string>>
     */
    private static function cookies(): iterable
    {
        $i = 0;

        yield ['Cookie ' . ++$i, 'Example value', 'https://example.com'];
        yield ['Cookie ' . ++$i, 'Example value', 'https://example.com'];
        yield ['Cookie ' . ++$i, 'Example value', 'https://example.com'];
    }
}
