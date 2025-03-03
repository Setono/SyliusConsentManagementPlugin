<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
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

    public function findOneByCode(string $code): ?CategoryInterface
    {
        $obj = $this->findOneBy(['code' => $code]);
        Assert::nullOrIsInstanceOf($obj, CategoryInterface::class);

        return $obj;
    }

    public function findNecessary(): array
    {
        $objs = $this
            ->createQueryBuilder('o')
            ->addOrderBy('o.position', 'ASC')
            ->andWhere('o.necessary = true')
            ->getQuery()
            ->getResult()
        ;

        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, Category::class);

        return $objs;
    }

    public function hasOne(): bool
    {
        return (int) $this->createQueryBuilder('o')
                ->select('COUNT(o)')
                ->getQuery()
                ->getSingleScalarResult() > 0
        ;
    }
}
