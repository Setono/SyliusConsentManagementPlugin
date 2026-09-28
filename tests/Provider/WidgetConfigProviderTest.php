<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider;

use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
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

    /**
     * @test
     */
    public function it_returns_the_existing_config(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $existingConfig = new WidgetConfig();

        $repository = $this->prophesize(WidgetConfigRepositoryInterface::class);
        $repository->findOneByChannelAndLocale($channel, 'en_US')->willReturn($existingConfig);

        $provider = $this->createProvider($repository->reveal(), $this->prophesize(WidgetConfigFactoryInterface::class)->reveal());

        self::assertSame($existingConfig, $provider->getWidgetConfig($channel, 'en_US'));
    }

    /**
     * @test
     */
    public function it_creates_the_config_when_none_exists(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $newConfig = new WidgetConfig();

        $repository = $this->prophesize(WidgetConfigRepositoryInterface::class);
        $repository->findOneByChannelAndLocale($channel, 'en_US')->willReturn(null);
        $repository->add($newConfig)->shouldBeCalledOnce();

        $factory = $this->prophesize(WidgetConfigFactoryInterface::class);
        $factory->createFromChannelAndLocale($channel, 'en_US')->willReturn($newConfig);

        self::assertSame($newConfig, $this->createProvider($repository->reveal(), $factory->reveal())->getWidgetConfig($channel, 'en_US'));
    }

    /**
     * @test
     */
    public function it_returns_the_config_created_by_a_concurrent_request(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $newConfig = new WidgetConfig();
        $concurrentlyCreatedConfig = new WidgetConfig();

        $repository = $this->prophesize(WidgetConfigRepositoryInterface::class);
        $repository->findOneByChannelAndLocale($channel, 'en_US')->willReturn(null, $concurrentlyCreatedConfig);
        $repository->add($newConfig)->willThrow(new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
        }, null));

        $factory = $this->prophesize(WidgetConfigFactoryInterface::class);
        $factory->createFromChannelAndLocale($channel, 'en_US')->willReturn($newConfig);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(WidgetConfig::class)->willReturn($entityManager);
        $managerRegistry->getManagers()->willReturn(['default' => $entityManager]);
        $managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($entityManager);

        $provider = $this->createProvider($repository->reveal(), $factory->reveal(), $managerRegistry->reveal());

        self::assertSame($concurrentlyCreatedConfig, $provider->getWidgetConfig($channel, 'en_US'));
    }

    private function createProvider(
        WidgetConfigRepositoryInterface $repository,
        WidgetConfigFactoryInterface $factory,
        ?ManagerRegistry $managerRegistry = null,
    ): WidgetConfigProvider {
        return new WidgetConfigProvider(
            $repository,
            $factory,
            $this->prophesize(ChannelContextInterface::class)->reveal(),
            $this->prophesize(LocaleContextInterface::class)->reveal(),
            $managerRegistry,
        );
    }
}
