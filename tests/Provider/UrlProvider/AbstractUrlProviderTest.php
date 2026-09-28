<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Fixture\TestUrlProvider;

final class AbstractUrlProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_generates_urls_for_channels_with_a_hostname(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getHostname()->willReturn('shop.example.com');

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate('sylius_shop_homepage', ['_locale' => 'en_US'], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/en_US/')
        ;

        self::assertSame(
            'https://shop.example.com/en_US/',
            $this->createProvider($urlGenerator->reveal())->generate($channel->reveal(), 'en_US', 'sylius_shop_homepage'),
        );
    }

    /**
     * @test
     */
    public function it_generates_absolute_urls_for_channels_without_a_hostname(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getHostname()->willReturn(null);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate('sylius_shop_homepage', ['_locale' => 'en_US'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/en_US/')
        ;

        self::assertSame(
            'http://localhost/en_US/',
            $this->createProvider($urlGenerator->reveal())->generate($channel->reveal(), 'en_US', 'sylius_shop_homepage'),
        );
    }

    private function createProvider(UrlGeneratorInterface $urlGenerator): TestUrlProvider
    {
        return new TestUrlProvider($this->prophesize(ManagerRegistry::class)->reveal(), $urlGenerator);
    }
}
