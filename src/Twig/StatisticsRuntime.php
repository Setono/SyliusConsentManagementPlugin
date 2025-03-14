<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @experimental
 */
final class StatisticsRuntime implements RuntimeExtensionInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        /** @var class-string<CategoryInterface> $categoryClass */
        private readonly string $categoryClass,

        /** @var class-string<ConsentEntryInterface> $consentEntryClass */
        private readonly string $consentEntryClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function consentEntryCount(string $timeThreshold = null): int
    {
        $qb = $this->getManager($this->consentEntryClass)
            ->createQueryBuilder()
            ->select('COUNT(o)')
            ->from($this->consentEntryClass, 'o')
        ;

        if (null !== $timeThreshold) {
            $qb->andWhere('o.createdAt >= :timeThreshold')
                ->setParameter('timeThreshold', new \DateTimeImmutable($timeThreshold))
            ;
        }

        return (int) $qb->getQuery()
            ->enableResultCache(60)
            ->getSingleScalarResult()
        ;
    }

    public function consentedCount(string $timeThreshold = null): int
    {
        $qb = $this->getManager($this->consentEntryClass)
            ->createQueryBuilder()
            ->select('COUNT(o)')
            ->from($this->consentEntryClass, 'o')
            ->andWhere('o.consentedCategories = :consentedCategories')
            ->setParameter('consentedCategories', implode(',', $this->getCategories()))
        ;

        if (null !== $timeThreshold) {
            $qb->andWhere('o.createdAt >= :timeThreshold')
                ->setParameter('timeThreshold', new \DateTimeImmutable($timeThreshold))
            ;
        }

        return (int) $qb->getQuery()
            ->enableResultCache(60)
            ->getSingleScalarResult()
        ;
    }

    /**
     * Returns a sorted list of ALL categories
     *
     * @return list<string>
     */
    private function getCategories(): array
    {
        /** @var array<array-key, array{code: string}> $codes */
        $codes = $this->getManager($this->categoryClass)
            ->createQueryBuilder()
            ->select('o.code')
            ->from($this->categoryClass, 'o')
            ->getQuery()
            ->getScalarResult()
        ;

        $codes = array_map(static fn (array $row): string => $row['code'], $codes);

        sort($codes);

        return $codes;
    }
}
