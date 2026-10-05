<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider;

use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProvider;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

final class WidgetConfigProviderTest extends TestCase
{
    use ProphecyTrait;

    private ChannelInterface $channel;

    /** @var ObjectProphecy<WidgetConfigRepositoryInterface> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<WidgetConfigFactoryInterface> */
    private ObjectProphecy $factory;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    protected function setUp(): void
    {
        $this->channel = $this->prophesize(ChannelInterface::class)->reveal();
        $this->repository = $this->prophesize(WidgetConfigRepositoryInterface::class);
        $this->factory = $this->prophesize(WidgetConfigFactoryInterface::class);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(WidgetConfig::class)->willReturn($entityManager);
        $this->managerRegistry->getManagers()->willReturn(['default' => $entityManager]);
    }

    /**
     * @test
     */
    public function it_returns_the_existing_config(): void
    {
        $existingConfig = new WidgetConfig();

        $this->repository->findOneByChannelAndLocale($this->channel, 'en_US')->willReturn($existingConfig);

        self::assertSame($existingConfig, $this->createProvider()->getWidgetConfig($this->channel, 'en_US'));
    }

    /**
     * @test
     */
    public function it_creates_the_config_when_none_exists(): void
    {
        $newConfig = new WidgetConfig();

        $this->repository->findOneByChannelAndLocale($this->channel, 'en_US')->willReturn(null);
        $this->repository->add($newConfig)->shouldBeCalledOnce();
        $this->factory->createFromChannelAndLocale($this->channel, 'en_US')->willReturn($newConfig);

        self::assertSame($newConfig, $this->createProvider()->getWidgetConfig($this->channel, 'en_US'));
    }

    /**
     * @test
     */
    public function it_returns_the_config_created_by_a_concurrent_request(): void
    {
        $newConfig = new WidgetConfig();
        $concurrentlyCreatedConfig = new WidgetConfig();

        $this->repository->findOneByChannelAndLocale($this->channel, 'en_US')->willReturn(null, $concurrentlyCreatedConfig);
        $this->repository->add($newConfig)->willThrow(self::createUniqueConstraintViolationException());
        $this->factory->createFromChannelAndLocale($this->channel, 'en_US')->willReturn($newConfig);
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce();

        self::assertSame($concurrentlyCreatedConfig, $this->createProvider()->getWidgetConfig($this->channel, 'en_US'));
    }

    /**
     * @test
     */
    public function it_rethrows_when_no_config_created_by_a_concurrent_request_is_found(): void
    {
        $newConfig = new WidgetConfig();
        $exception = self::createUniqueConstraintViolationException();

        $this->repository->findOneByChannelAndLocale($this->channel, 'en_US')->willReturn(null);
        $this->repository->add($newConfig)->willThrow($exception);
        $this->factory->createFromChannelAndLocale($this->channel, 'en_US')->willReturn($newConfig);
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce();

        $this->expectExceptionObject($exception);

        $this->createProvider()->getWidgetConfig($this->channel, 'en_US');
    }

    /**
     * @test
     */
    public function it_rethrows_when_the_manager_cannot_be_reset(): void
    {
        $newConfig = new WidgetConfig();
        $exception = self::createUniqueConstraintViolationException();

        $this->repository->findOneByChannelAndLocale($this->channel, 'en_US')->shouldBeCalledOnce()->willReturn(null);
        $this->repository->add($newConfig)->willThrow($exception);
        $this->factory->createFromChannelAndLocale($this->channel, 'en_US')->willReturn($newConfig);
        $this->managerRegistry->getManagers()->willReturn([]);
        $this->managerRegistry->resetManager(Argument::any())->shouldNotBeCalled();

        $this->expectExceptionObject($exception);

        $this->createProvider()->getWidgetConfig($this->channel, 'en_US');
    }

    private function createProvider(): WidgetConfigProvider
    {
        return new WidgetConfigProvider(
            $this->repository->reveal(),
            $this->factory->reveal(),
            $this->prophesize(ChannelContextInterface::class)->reveal(),
            $this->prophesize(LocaleContextInterface::class)->reveal(),
            $this->managerRegistry->reveal(),
        );
    }

    private static function createUniqueConstraintViolationException(): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
        }, null);
    }
}
