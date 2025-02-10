<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\Category;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class CategoryRepository extends EntityRepository implements CategoryRepositoryInterface
{
    public function findAll(): array
    {
        $objs = $this
            ->createQueryBuilder('o')
            ->addOrderBy('o.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, Category::class);

        return $objs;
    }
}
