<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
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

    public function findByNames(array $names): array
    {
        if ([] === $names) {
            return [];
        }

        $objs = $this->createQueryBuilder('o', 'o.name')
            ->andWhere('o.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($objs);
        Assert::allIsInstanceOf($objs, CookieInterface::class);

        /** @var array<string, CookieInterface> $cookies */
        $cookies = $objs;

        return $cookies;
    }

    public function findStaleCookies(string $staleThreshold): array
    {
        $objs = $this->createQueryBuilder('o')
            ->andWhere('o.state = :state')
            ->andWhere('o.lastSeenAt < :threshold')
            ->setParameter('state', CookieInterface::STATE_CONFIRMED)
            ->setParameter('threshold', new \DateTimeImmutable($staleThreshold))
            ->getQuery()
            ->getResult()
        ;

        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, CookieInterface::class);

        return $objs;
    }

    public function prune(): void
    {
        $this->createQueryBuilder('o')
            ->delete()
            ->andWhere('o.state = :state')
            ->andWhere('o.updatedAt < :threshold')
            ->setParameter('state', CookieInterface::STATE_PENDING)
            ->setParameter('threshold', new \DateTimeImmutable('-1 month'))
            ->getQuery()
            ->execute()
        ;
    }
}
