<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Recorder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorder;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

        $this->recorder->record(['_ga' => $this->createCookie('_ga'), '_fbp' => $this->createCookie('_fbp')]);

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

        $this->recorder->record(['_fbp' => $this->createCookie('_fbp')]);

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

        $this->recorder->record(['_ga' => $this->createCookie('_ga')]);

        self::assertSame([['name' => '_ga', 'samples' => 3, 'state' => 'confirmed']], $this->getCookies());
        self::assertSame(['_ga'], $confirmed, 'NotifyAboutCookiesSubscriber collects the confirmed cookies from this event');
    }

    /**
     * @test
     */
    public function it_updates_the_cookie_a_concurrent_request_saved_first_and_saves_the_other_new_cookies(): void
    {
        // The concurrent request saves '_fbp' after this request looked it up, but before it saved it
        $concurrentRequest = new class($this->connection) {
            private bool $done = false;

            public function __construct(private readonly Connection $connection)
            {
            }

            public function preFlush(): void
            {
                if ($this->done) {
                    return;
                }

                $this->done = true;
                $this->connection->insert('setono_sylius_consent_management__cookie', [
                    'name' => '_fbp',
                    'state' => 'pending',
                    'samples' => 1,
                    'session' => 0,
                    'lastSeenAt' => '2024-01-01 00:00:00',
                    'createdAt' => '2024-01-01 00:00:00',
                ]);
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::preFlush], $concurrentRequest);

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        // '_gid' comes first, so the failed flush inserted it before it failed on '_fbp'
        $this->recorder->record(['_gid' => $this->createCookie('_gid'), '_fbp' => $this->createCookie('_fbp')]);

        self::assertSame([
            ['name' => '_fbp', 'samples' => 2, 'state' => 'pending'],
            ['name' => '_gid', 'samples' => 1, 'state' => 'pending'],
        ], $this->getCookies());
        self::assertSame(['_gid'], $this->getCookiesInChannel());

        // Later calls in the same process, e.g. during a crawl, still work
        $this->recorder->record(['_gcl_au' => $this->createCookie('_gcl_au')]);

        self::assertSame(['_gcl_au', '_gid'], $this->getCookiesInChannel());
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

        $this->recorder->record(['_ga' => $this->createCookie('_ga')]);

        self::assertSame([], $this->getCookies());

        $this->recorder->record(['_fbp' => $this->createCookie('_fbp')]);

        self::assertSame([['name' => '_fbp', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_records_names_that_only_differ_in_case_as_the_existing_cookie(): void
    {
        $this->createExistingCookie('_ga', 1);

        $this->logger->error(Argument::cetera())->shouldNotBeCalled();

        $this->recorder->record(['_GA' => $this->createCookie('_GA'), '_fbp' => $this->createCookie('_fbp')]);

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
            '_ga' => $this->createCookie('_ga'),
            '_GA' => $this->createCookie('_GA'),
            '_fbp' => $this->createCookie('_fbp'),
            '_FBP' => $this->createCookie('_FBP'),
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
            '_ga' => $this->createCookie('_ga'),
            str_repeat('x', 256) => $this->createCookie(str_repeat('x', 256)),
        ]);

        self::assertSame([['name' => '_ga', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
    }

    /**
     * @test
     */
    public function it_skips_names_that_cannot_be_cookie_names(): void
    {
        $this->recorder->record([
            '_ga' => $this->createCookie('_ga'),
            'ai_session.v1' => $this->createCookie('ai_session.v1'),
            'VISIT evil.example TO VERIFY YOUR STORE' => $this->createCookie('VISIT evil.example TO VERIFY YOUR STORE'),
            '<script>' => $this->createCookie('<script>'),
            '' => $this->createCookie(''),
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
            $cookies[$name] = $this->createCookie($name);
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
    public function it_handles_numeric_cookie_names(): void
    {
        // PHP turns the numeric string key into an integer, which is exactly what this test is about
        $this->recorder->record(['123' => $this->createCookie('123')]); // @phpstan-ignore argument.type

        self::assertSame([['name' => '123', 'samples' => 1, 'state' => 'pending']], $this->getCookies());
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
