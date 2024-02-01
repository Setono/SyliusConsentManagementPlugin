<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactory;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactory
 */
final class WidgetConfigFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_creates_with_data(): void
    {
        $channel = new Channel();

        $factory = $this->getFactory();
        $obj = $factory->createFromChannelAndLocale($channel, 'en_US');

        $locale = $obj->getLocale();
        self::assertNotNull($locale);
        self::assertSame($channel, $obj->getChannel());
        self::assertSame('en_US', $locale->getCode());
        self::assertSame('usage description', $obj->getUsageDescription());
    }

    private function getFactory(): WidgetConfigFactoryInterface
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::cetera())->willReturn('usage description');

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findOneBy(Argument::cetera())->will(function (array $args) {
            Assert::count($args, 1);
            Assert::isArray($args[0]);
            Assert::keyExists($args[0], 'code');
            Assert::string($args[0]['code']);

            $locale = new Locale();
            $locale->setCode($args[0]['code']);

            return $locale;
        });

        return new WidgetConfigFactory(new Factory(WidgetConfig::class), $repository->reveal(), $translator->reveal());
    }
}
