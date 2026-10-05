<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Twig\StatisticsRuntime;

final class StatisticsRuntimeTest extends TestCase
{
    use ProphecyTrait;

    /** @var list<string> */
    private array $timeThresholds = [];

    /**
     * @test
     */
    public function it_truncates_the_time_threshold_to_the_hour(): void
    {
        $runtime = $this->createRuntime();

        self::assertSame(3, $runtime->consentEntryCount('2026-09-28 14:20:08.719091'));
        self::assertSame(3, $runtime->consentedCount('2026-09-28 14:20:08.719091'));

        self::assertSame(['2026-09-28 14:00:00.000000', '2026-09-28 14:00:00.000000'], $this->timeThresholds);
    }

    /**
     * @test
     */
    public function it_uses_the_same_time_threshold_on_repeated_calls_so_the_result_cache_key_is_stable(): void
    {
        $runtime = $this->createRuntime();

        $runtime->consentEntryCount('-30 days');
        $runtime->consentEntryCount('-30 days');
        $runtime->consentedCount('-30 days');
        $runtime->consentedCount('-30 days');

        self::assertCount(4, $this->timeThresholds);

        // Only an hour boundary between the calls could make the thresholds differ
        self::assertCount(1, array_unique($this->timeThresholds));
    }

    private function createRuntime(): StatisticsRuntime
    {
        $query = $this->prophesize(Query::class);
        $query->enableResultCache(60)->willReturn($query);
        $query->getSingleScalarResult()->willReturn(3);
        $query->getScalarResult()->willReturn([['code' => 'necessary'], ['code' => 'marketing']]);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->from(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->andWhere(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->setParameter('consentedCategories', 'marketing,necessary')->willReturn($queryBuilder);
        $queryBuilder->setParameter('timeThreshold', Argument::that(function (mixed $value): bool {
            if (!$value instanceof \DateTimeImmutable) {
                return false;
            }

            $this->timeThresholds[] = $value->format('Y-m-d H:i:s.u');

            return true;
        }))->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(ConsentEntry::class)->willReturn($entityManager);
        $managerRegistry->getManagerForClass(Category::class)->willReturn($entityManager);

        return new StatisticsRuntime($managerRegistry->reveal(), Category::class, ConsentEntry::class);
    }
}
