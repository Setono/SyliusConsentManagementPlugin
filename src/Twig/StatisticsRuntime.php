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

    public function consentEntryCount(?string $timeThreshold = null): int
    {
        $qb = $this->getManager($this->consentEntryClass)
            ->createQueryBuilder()
            ->select('COUNT(o)')
            ->from($this->consentEntryClass, 'o')
        ;

        if (null !== $timeThreshold) {
            $qb->andWhere('o.createdAt >= :timeThreshold')
                ->setParameter('timeThreshold', self::createTimeThreshold($timeThreshold))
            ;
        }

        return (int) $qb->getQuery()
            ->enableResultCache(60)
            ->getSingleScalarResult()
        ;
    }

    public function consentedCount(?string $timeThreshold = null): int
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
                ->setParameter('timeThreshold', self::createTimeThreshold($timeThreshold))
            ;
        }

        return (int) $qb->getQuery()
            ->enableResultCache(60)
            ->getSingleScalarResult()
        ;
    }

    /**
     * The threshold is truncated to the hour. Otherwise the query parameter, and with it the result cache key,
     * would change on every call and the result cache would never be hit. Truncating to the hour rather than the
     * minute also means an expired cache item is overwritten under the same key instead of being left behind
     */
    private static function createTimeThreshold(string $timeThreshold): \DateTimeImmutable
    {
        $threshold = new \DateTimeImmutable($timeThreshold);

        return $threshold->setTime((int) $threshold->format('G'), 0);
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

        sort($codes, \SORT_STRING);

        return $codes;
    }
}
