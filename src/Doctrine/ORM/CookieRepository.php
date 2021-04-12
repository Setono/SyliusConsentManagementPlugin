<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class CookieRepository extends EntityRepository implements CookieRepositoryInterface
{
    public function findOneByName(string $name): ?CookieInterface
    {
        $obj = $this->findOneBy([
            'name' => $name,
        ]);

        Assert::nullOrIsInstanceOf($obj, CookieInterface::class);

        return $obj;
    }
}
