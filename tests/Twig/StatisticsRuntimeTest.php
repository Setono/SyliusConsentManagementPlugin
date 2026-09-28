<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Doctrine\ORM\EntityManagerInterface;
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

    /** @var list<mixed> */
    private array $timeThresholds = [];

    /**
     * @test
     */
    public function it_truncates_the_time_threshold_so_the_result_cache_key_is_stable(): void
    {
        $runtime = $this->createRuntime();

        $this->countIgnoringQueryExecution(static fn () => $runtime->consentEntryCount('-30 days'));
        $this->countIgnoringQueryExecution(static fn () => $runtime->consentEntryCount('-30 days'));

        self::assertCount(2, $this->timeThresholds);
        [$first, $second] = $this->timeThresholds;
        self::assertInstanceOf(\DateTimeImmutable::class, $first);
        self::assertInstanceOf(\DateTimeImmutable::class, $second);

        self::assertSame('00.000000', $first->format('s.u'));

        // Two calls in the same minute must produce the same parameter (and hence the same result cache key).
        // A minute boundary between the two calls is tolerated to avoid a flaky test
        self::assertLessThanOrEqual(60, $second->getTimestamp() - $first->getTimestamp());
        self::assertEqualsWithDelta((new \DateTimeImmutable('-30 days'))->getTimestamp(), $first->getTimestamp(), 120);
    }

    private function createRuntime(): StatisticsRuntime
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->from(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->andWhere(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->setParameter('timeThreshold', Argument::that(function (mixed $value): bool {
            $this->timeThresholds[] = $value;

            return true;
        }))->willReturn($queryBuilder);
        // Doctrine\ORM\Query is final and can't be doubled, so stop before the query is executed
        $queryBuilder->getQuery()->willThrow(new \LogicException('Query execution is not part of this test'));

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(ConsentEntry::class)->willReturn($entityManager);

        return new StatisticsRuntime($managerRegistry->reveal(), Category::class, ConsentEntry::class);
    }

    private function countIgnoringQueryExecution(callable $count): void
    {
        try {
            $count();
        } catch (\LogicException) {
        }
    }
}
