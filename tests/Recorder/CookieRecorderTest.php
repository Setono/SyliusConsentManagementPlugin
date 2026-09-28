<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Recorder;

use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorder;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;

final class CookieRecorderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CookieRepositoryInterface> */
    private ObjectProphecy $cookieRepository;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    private CookieRecorder $recorder;

    protected function setUp(): void
    {
        $this->cookieRepository = $this->prophesize(CookieRepositoryInterface::class);
        $this->cookieRepository->getClassName()->willReturn(Cookie::class);

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->entityManager->isOpen()->willReturn(true);

        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(Cookie::class)->willReturn($this->entityManager);
        $this->managerRegistry->getManagers()->willReturn(['default' => $this->entityManager]);

        $this->recorder = new CookieRecorder($this->cookieRepository->reveal(), $this->managerRegistry->reveal());
    }

    /**
     * @test
     */
    public function it_updates_existing_cookies_and_saves_new_cookies_in_one_flush(): void
    {
        $existingCookie = self::createCookie('_ga', 2);
        $newCookie = self::createCookie('_fbp');

        $this->cookieRepository->findByNames(['_ga', '_fbp'])->willReturn(['_ga' => $existingCookie]);
        $this->entityManager->persist($newCookie)->shouldBeCalledOnce();
        $this->entityManager->persist($existingCookie)->shouldNotBeCalled();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->recorder->record(['_ga' => self::createCookie('_ga'), '_fbp' => $newCookie]);

        self::assertSame(3, $existingCookie->getSamples());
        self::assertSame(1, $newCookie->getSamples());
        self::assertNotNull($existingCookie->getLastSeenAt());
        self::assertNotNull($newCookie->getLastSeenAt());
    }

    /**
     * @test
     */
    public function it_updates_the_cookie_created_by_a_concurrent_request(): void
    {
        $newCookie = self::createCookie('_fbp');
        $concurrentlyCreatedCookie = self::createCookie('_fbp', 1);

        $this->cookieRepository->findByNames(['_fbp'])->willReturn([], ['_fbp' => $concurrentlyCreatedCookie]);

        $flushes = 0;
        $this->entityManager->persist($newCookie)->shouldBeCalledOnce();
        $this->entityManager->flush()->will(function () use (&$flushes): void {
            if (1 === ++$flushes) {
                throw new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
                }, null);
            }
        });
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($this->entityManager);

        $this->recorder->record(['_fbp' => $newCookie]);

        self::assertSame(2, $flushes);
        self::assertSame(2, $concurrentlyCreatedCookie->getSamples());
    }

    /**
     * @test
     */
    public function it_logs_failures_instead_of_throwing_and_resets_a_closed_entity_manager(): void
    {
        $this->cookieRepository->findByNames(['_ga'])->willReturn([]);
        $this->entityManager->persist(Argument::any())->shouldBeCalled();
        $this->entityManager->flush()->willThrow(new \RuntimeException('Lock wait timeout exceeded'));
        $this->entityManager->isOpen()->willReturn(false);
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($this->entityManager);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error('Could not record cookies: {message}', Argument::withEntry('message', 'Lock wait timeout exceeded'))->shouldBeCalledOnce();
        $this->recorder->setLogger($logger->reveal());

        $this->recorder->record(['_ga' => self::createCookie('_ga')]);
    }

    /**
     * @test
     */
    public function it_skips_names_that_do_not_fit_the_database_column(): void
    {
        $this->cookieRepository->findByNames(['_ga'])->willReturn([])->shouldBeCalledOnce();
        $this->entityManager->persist(Argument::any())->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->recorder->record([
            '_ga' => self::createCookie('_ga'),
            str_repeat('x', 256) => self::createCookie(str_repeat('x', 256)),
        ]);
    }

    /**
     * @test
     */
    public function it_handles_numeric_cookie_names(): void
    {
        $this->cookieRepository->findByNames(['123'])->willReturn([])->shouldBeCalledOnce();
        $this->entityManager->persist(Argument::any())->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        // PHP turns the numeric string key into an integer, which is exactly what this test is about
        $this->recorder->record(['123' => self::createCookie('123')]); // @phpstan-ignore argument.type
    }

    /**
     * @test
     */
    public function it_does_nothing_without_cookies(): void
    {
        $this->cookieRepository->findByNames(Argument::any())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->recorder->record([]);
    }

    private static function createCookie(string $name, int $samples = 0): Cookie
    {
        $cookie = new Cookie();
        $cookie->setName($name);
        $cookie->setSamples($samples);

        return $cookie;
    }
}
