<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Recorder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesServerSideSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorder;
use Setono\SyliusConsentManagementPlugin\Sampling\SamplingRateLimiter;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Factory\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Records cookies in an SQLite database, using the Doctrine configuration and listeners of the test application.
 * Like MySQL's default collation, the database compares cookie names case-insensitively
 */
final class CookieRecorderTest extends KernelTestCase
{
    use ProphecyTrait;

    /** @var list<string>|null */
    private static ?array $schema = null;

    private Connection $connection;

    /**
     * The entity manager of the request, i.e. the default entity manager
     */
    private EntityManagerInterface $entityManager;

    /** @var ObjectProphecy<LoggerInterface> */
    private ObjectProphecy $logger;

    private CookieRecorder $recorder;

    private ChannelInterface $channel;

    protected function setUp(): void
    {
        self::bootKernel();

        $defaultEntityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $defaultEntityManager);

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->entityManager = new EntityManager($this->connection, $defaultEntityManager->getConfiguration(), $defaultEntityManager->getEventManager());

        foreach ($this->getSchema() as $sql) {
            $this->connection->executeStatement($sql);
        }

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass($this->getCookieClass())->willReturn($this->entityManager);

        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->recorder = new CookieRecorder($managerRegistry->reveal(), $this->getCookieClass());
        $this->recorder->setLogger($this->logger->reveal());

        $this->channel = $this->createChannel();
    }

    /**
     * @test
     */
    public function it_updates_existing_cookies_and_saves_new_cookies(): void
    {
        $this->createExistingCookie('_ga', 1);

        $this->recorder->record([$this->createCookie('_ga'), $this->createCookie('_fbp')]);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 2, 'state' => 'pending'],
        ], $this->getCookies());
        self::assertSame(['_fbp', '_ga'], $this->getCookiesInChannel());
    }

    /**
     * @test
     */
    public function it_does_not_save_changes_the_request_left_unsaved(): void
    {
        // E.g. a form was mapped onto the channel, but failed validation, so the request didn't save it
        $this->channel->setName('Unsaved name');

        $this->recorder->record([$this->createCookie('_fbp')]);

        self::assertSame('Web', $this->connection->fetchOne('SELECT name FROM sylius_channel'));
        self::assertSame([['name' => '_fbp', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
        self::assertSame(['_fbp'], $this->getCookiesInChannel());
    }

    /**
     * @test
     */
    public function it_confirms_cookies_with_enough_samples(): void
    {
        $this->createExistingCookie('_ga', 2);

        $confirmed = [];
        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        $eventDispatcher->addListener(
            'workflow.setono_sylius_consent_management__cookie.completed.confirm',
            static function (CompletedEvent $event) use (&$confirmed): void {
                $cookie = $event->getSubject();
                self::assertInstanceOf(CookieInterface::class, $cookie);

                $confirmed[] = $cookie->getName();
            },
        );

        $this->recorder->record([$this->createCookie('_ga')]);

        self::assertSame([['name' => '_ga', 'samples' => 3, 'state' => 'confirmed']], $this->getCookies());
        self::assertSame(['_ga'], $confirmed, 'NotifyAboutCookiesSubscriber collects the confirmed cookies from this event');
    }

    /**
     * @test
     */
    public function it_updates_the_cookie_a_concurrent_request_saved_first_and_saves_the_other_new_cookies(): void
    {
        $this->insertConcurrentlyBeforeTheFirstFlush('_fbp');

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        // '_gid' comes first, so the failed flush inserted it before it failed on '_fbp'. A generator can only be
        // iterated once, so this also shows that the retry doesn't iterate the given cookies again
        $this->recorder->record((function (): \Generator {
            yield $this->createCookie('_gid');
            yield $this->createCookie('_fbp');
        })());

        self::assertSame([
            ['name' => '_fbp', 'samples' => 2, 'state' => 'pending'],
            ['name' => '_gid', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
        self::assertSame(['_gid'], $this->getCookiesInChannel());

        // Later calls in the same process, e.g. during a crawl, still work
        $this->recorder->record([$this->createCookie('_gcl_au')]);

        self::assertSame(['_gcl_au', '_gid'], $this->getCookiesInChannel());
    }

    /**
     * @test
     */
    public function it_saves_only_as_many_new_cookies_as_the_limiter_allows(): void
    {
        $this->createExistingCookie('_ga', 0);
        $limiter = self::createNewCookieLimiter(2);

        $this->logger
            ->info('Skipped {count} new cookies, because the client has introduced too many new cookies recently', ['count' => 1, 'names' => ['_gcl_au']])
            ->shouldBeCalledTimes(2)
        ;

        // '_fbp' and '_FBP' are one new cookie
        $this->recorder->record([
            $this->createCookie('_ga'),
            $this->createCookie('_fbp'),
            $this->createCookie('_FBP'),
            $this->createCookie('_gid'),
            $this->createCookie('_gcl_au'),
        ], $limiter);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_gid', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());

        // With the limit used up, existing cookies are still recorded
        $this->recorder->record([$this->createCookie('_ga'), $this->createCookie('_fbp'), $this->createCookie('_gcl_au')], $limiter);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 2, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 2, 'state' => 'pending'],
            ['name' => '_gid', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_does_not_consume_the_limiter_again_when_it_retries(): void
    {
        $this->insertConcurrentlyBeforeTheFirstFlush('_fbp');
        $limiter = self::createNewCookieLimiter(2);

        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        // The first attempt admits both new cookies and fails on '_fbp'. The retry must still save '_gid'
        $this->recorder->record([$this->createCookie('_gid'), $this->createCookie('_fbp')], $limiter);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 2, 'state' => 'pending'],
            ['name' => '_gid', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
        self::assertSame(0, $limiter->consume(0)->getRemainingTokens());
    }

    /**
     * @test
     */
    public function it_logs_failures_instead_of_throwing_and_keeps_recording_afterwards(): void
    {
        // A failing insert closes the entity manager
        $failingInsert = new class() {
            private bool $done = false;

            public function postPersist(): void
            {
                if (!$this->done) {
                    $this->done = true;

                    throw new \RuntimeException('Lock wait timeout exceeded');
                }
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::postPersist], $failingInsert);

        $this->logger
            ->error('Could not record cookies: {message}', Argument::withEntry('message', 'Lock wait timeout exceeded'))
            ->shouldBeCalledOnce()
        ;

        $this->recorder->record([$this->createCookie('_ga')]);

        self::assertSame([], $this->getCookies());

        $this->recorder->record([$this->createCookie('_fbp')]);

        self::assertSame([['name' => '_fbp', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_retries_a_unique_constraint_violation_only_once(): void
    {
        // E.g. other requests keep saving one of the new cookies first
        $failingFlush = new class() {
            public int $flushes = 0;

            public function preFlush(): never
            {
                ++$this->flushes;

                throw new UniqueConstraintViolationException(PDOException::new(new \PDOException('Duplicate entry')), null);
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::preFlush], $failingFlush);

        $this->logger
            ->error('Could not record cookies: {message}', Argument::withEntry('message', 'An exception occurred in the driver: Duplicate entry'))
            ->shouldBeCalledOnce()
        ;

        $this->recorder->record([$this->createCookie('_ga')]);

        self::assertSame(2, $failingFlush->flushes);
        self::assertSame([], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_records_names_that_only_differ_in_case_as_the_existing_cookie(): void
    {
        $this->createExistingCookie('_ga', 1);

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        $this->recorder->record([$this->createCookie('_GA'), $this->createCookie('_fbp')]);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 2, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_records_cookies_with_the_same_name_once(): void
    {
        $this->createExistingCookie('_ga', 1);

        $this->recorder->record([
            $this->createCookie('_ga'),
            $this->createCookie('_fbp'),
            $this->createCookie('_ga'),
            $this->createCookie('_fbp'),
        ]);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 2, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_counts_names_that_only_differ_in_case_once(): void
    {
        $this->createExistingCookie('_ga', 1);

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        $this->recorder->record([
            $this->createCookie('_ga'),
            $this->createCookie('_GA'),
            $this->createCookie('_fbp'),
            $this->createCookie('_FBP'),
        ]);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 1, 'state' => 'pending'],
            ['name' => '_ga', 'samples' => 2, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_skips_names_that_do_not_fit_the_database_column(): void
    {
        $this->recorder->record([
            $this->createCookie('_ga'),
            $this->createCookie(str_repeat('x', 256)),
        ]);

        self::assertSame([['name' => '_ga', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_skips_and_logs_names_that_cannot_be_cookie_names(): void
    {
        // E.g. so that a store notices its own cookies with such names aren't recorded by the crawler
        $this->logger->info('Skipped {count} cookies with invalid names', [
            'count' => 3,
            'names' => ['VISIT evil.example TO VERIFY YOUR STORE', '<script>', ''],
        ])->shouldBeCalledOnce();

        $this->recorder->record([
            $this->createCookie('_ga'),
            $this->createCookie('ai_session.v1'),
            $this->createCookie('VISIT evil.example TO VERIFY YOUR STORE'),
            $this->createCookie('<script>'),
            $this->createCookie(''),
        ]);

        self::assertSame([
            ['name' => '_ga', 'samples' => 1, 'state' => 'pending'],
            ['name' => 'ai_session.v1', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_records_a_limited_number_of_cookies_per_call(): void
    {
        // Skipped names don't count
        $names = ['VISIT evil.example', 'TO VERIFY', 'YOUR STORE'];
        foreach (range(1, CookieRecorder::MAX_COOKIES + 10) as $i) {
            $names[] = sprintf('cookie%03d', $i);
        }

        $cookies = [];
        foreach ($names as $name) {
            $cookies[] = $this->createCookie($name);
        }

        $this->recorder->record($cookies);

        $recordedNames = array_column($this->getCookies(), 'name');
        self::assertCount(CookieRecorder::MAX_COOKIES, $recordedNames);
        self::assertSame('cookie001', $recordedNames[0]);
        self::assertSame(sprintf('cookie%03d', CookieRecorder::MAX_COOKIES), $recordedNames[CookieRecorder::MAX_COOKIES - 1]);
    }

    /**
     * @test
     */
    public function it_does_not_record_forged_names_from_a_sampled_cookie_header(): void
    {
        $header = ['VISIT evil.example TO VERIFY YOUR STORE=1'];
        foreach (range(1, CookieRecorder::MAX_COOKIES + 10) as $i) {
            $header[] = sprintf('cookie%03d=1', $i);
        }

        // Without rate limits, to show the name rule and the cap
        $subscriber = $this->createServerSideSubscriber(['policy' => 'no_limit'], ['policy' => 'no_limit']);

        $this->sampleServerSide($subscriber, implode('; ', $header), '192.0.2.1');

        $recordedNames = array_column($this->getCookies(), 'name');
        self::assertCount(CookieRecorder::MAX_COOKIES, $recordedNames);
        self::assertNotContains('VISIT evil.example TO VERIFY YOUR STORE', $recordedNames);
        self::assertSame(sprintf('cookie%03d', CookieRecorder::MAX_COOKIES), $recordedNames[CookieRecorder::MAX_COOKIES - 1]);
    }

    /**
     * @test
     */
    public function it_bounds_the_new_names_that_forged_cookie_headers_can_introduce(): void
    {
        // The default limits
        $subscriber = $this->createServerSideSubscriber(
            ['policy' => 'sliding_window', 'limit' => 10, 'interval' => '1 hour'],
            ['policy' => 'sliding_window', 'limit' => 10, 'interval' => '1 day'],
        );

        foreach (range(0, 4) as $request) {
            $header = array_map(static fn (int $i): string => sprintf('forged%03d=1', $request * 50 + $i), range(1, 50));
            $this->sampleServerSide($subscriber, implode('; ', $header), '192.0.2.1');
        }

        self::assertCount(10, $this->getCookies());

        // Another client
        $this->sampleServerSide($subscriber, 'other1=1; other2=1', '192.0.2.2');

        self::assertCount(12, $this->getCookies());
    }

    /**
     * @param array<string, mixed> $recordingsLimit
     * @param array<string, mixed> $newCookiesLimit
     */
    private function createServerSideSubscriber(array $recordingsLimit, array $newCookiesLimit): SampleCookiesServerSideSubscriber
    {
        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn($this->channel);

        $sampleDecider = $this->prophesize(SampleDeciderInterface::class);
        $sampleDecider->sample(Argument::cetera())->willReturn(true);

        return new SampleCookiesServerSideSubscriber(
            $this->recorder,
            new CookieFactory(new Factory($this->getCookieClass()), $channelContext->reveal(), new RequestStack()),
            $sampleDecider->reveal(),
            new SamplingRateLimiter(
                new RateLimiterFactory(['id' => 'recordings'] + $recordingsLimit, new InMemoryStorage()),
                new RateLimiterFactory(['id' => 'new_cookies'] + $newCookiesLimit, new InMemoryStorage()),
            ),
        );
    }

    private function sampleServerSide(SampleCookiesServerSideSubscriber $subscriber, string $cookieHeader, string $clientIp): void
    {
        $kernel = $this->prophesize(HttpKernelInterface::class)->reveal();
        $request = Request::create('/', server: ['HTTP_COOKIE' => $cookieHeader, 'REMOTE_ADDR' => $clientIp]);

        $subscriber->sample(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->record(new TerminateEvent($kernel, $request, new Response()));
    }

    /**
     * @test
     */
    public function it_handles_numeric_cookie_names(): void
    {
        // The recorder looks cookies up by name, and PHP turns numeric names into integer array keys
        $this->createExistingCookie('123', 1);

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        $this->recorder->record([$this->createCookie('123'), $this->createCookie('456'), $this->createCookie('456')]);

        self::assertSame([
            ['name' => '123', 'samples' => 2, 'state' => 'pending'],
            ['name' => '456', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_does_nothing_without_cookies(): void
    {
        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        $this->recorder->record([]);

        self::assertSame([], $this->getCookies());
    }

    /**
     * @return list<array{name: string, samples: int, state: string}>
     */
    private function getCookies(): array
    {
        /** @var list<array{name: string, samples: int|string, state: string}> $rows */
        $rows = $this->connection->fetchAllAssociative('SELECT name, samples, state FROM setono_sylius_consent_management__cookie ORDER BY name');

        return array_map(static fn (array $row): array => [
            'name' => $row['name'],
            'samples' => (int) $row['samples'],
            'state' => $row['state'],
        ], $rows);
    }

    /**
     * @return list<string>
     */
    private function getCookiesInChannel(): array
    {
        /** @var list<string> $names */
        $names = $this->connection->fetchFirstColumn(
            'SELECT c.name FROM setono_sylius_consent_management__cookie c JOIN setono_sylius_consent_management__cookie_channels cc ON cc.cookie_id = c.id WHERE cc.channel_id = ? ORDER BY c.name',
            [$this->channel->getId()],
        );

        return $names;
    }

    /**
     * A concurrent request saves the cookie after this request looked it up, but before it saved it
     */
    private function insertConcurrentlyBeforeTheFirstFlush(string $name): void
    {
        $concurrentRequest = new class($this->connection, $name) {
            private bool $done = false;

            public function __construct(private readonly Connection $connection, private readonly string $name)
            {
            }

            public function preFlush(): void
            {
                if ($this->done) {
                    return;
                }

                $this->done = true;
                $this->connection->insert('setono_sylius_consent_management__cookie', [
                    'name' => $this->name,
                    'state' => 'pending',
                    'samples' => 1,
                    'session' => 0,
                    'lastSeenAt' => '2024-01-01 00:00:00',
                    'createdAt' => '2024-01-01 00:00:00',
                ]);
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::preFlush], $concurrentRequest);
    }

    private static function createNewCookieLimiter(int $limit): LimiterInterface
    {
        return (new RateLimiterFactory(['id' => 'new_cookies', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 day'], new InMemoryStorage()))->create('192.0.2.1');
    }

    private function createExistingCookie(string $name, int $samples): void
    {
        $cookie = $this->createCookie($name);
        $cookie->setSamples($samples);

        $this->entityManager->persist($cookie);
        $this->entityManager->flush();
    }

    /**
     * Creates the cookie like the cookie factory does, i.e. with the channel of the request
     */
    private function createCookie(string $name): CookieInterface
    {
        $class = $this->getCookieClass();
        $cookie = new $class();
        $cookie->setName($name);
        $cookie->addChannel($this->channel);

        return $cookie;
    }

    /**
     * Inserted with SQL, because Sylius' Doctrine listeners would query the application's database when saving a new channel
     */
    private function createChannel(): ChannelInterface
    {
        $now = date('Y-m-d H:i:s');
        $this->connection->insert('sylius_locale', ['id' => 1, 'code' => 'en_US', 'created_at' => $now]);
        $this->connection->insert('sylius_currency', ['id' => 1, 'code' => 'USD', 'created_at' => $now]);
        $this->connection->insert('sylius_channel', [
            'id' => 1,
            'code' => 'WEB',
            'name' => 'Web',
            'enabled' => 1,
            'default_locale_id' => 1,
            'base_currency_id' => 1,
            'tax_calculation_strategy' => 'order_items_based',
            'skipping_shipping_step_allowed' => 0,
            'skipping_payment_step_allowed' => 0,
            'account_verification_required' => 1,
            'created_at' => $now,
        ]);

        // Like the request loads the channel, e.g. through the channel context
        $channel = $this->entityManager->find(ChannelInterface::class, 1);
        self::assertInstanceOf(ChannelInterface::class, $channel);

        return $channel;
    }

    /**
     * @return class-string<CookieInterface>
     */
    private function getCookieClass(): string
    {
        $class = self::getContainer()->getParameter('setono_sylius_consent_management.model.cookie.class');
        self::assertIsString($class);
        self::assertTrue(is_a($class, CookieInterface::class, true));

        return $class;
    }

    /**
     * @return list<string>
     */
    private function getSchema(): array
    {
        if (null === self::$schema) {
            $schema = (new SchemaTool($this->entityManager))->getSchemaFromMetadata($this->entityManager->getMetadataFactory()->getAllMetadata());
            $schema->getTable('setono_sylius_consent_management__cookie')->getColumn('name')->setPlatformOption('collation', 'NOCASE');

            self::$schema = $schema->toSql($this->connection->getDatabasePlatform());
        }

        return self::$schema;
    }
}
