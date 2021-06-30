<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactory;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactory
 */
final class WidgetConfigFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_with_data(): void
    {
        $channel = new Channel();

        $factory = self::getFactory();
        $obj = $factory->createFromChannelAndLocale($channel, 'en_US');

        $locale = $obj->getLocale();
        self::assertNotNull($locale);
        self::assertSame($channel, $obj->getChannel());
        self::assertSame('en_US', $locale->getCode());
        self::assertSame('usage description', $obj->getUsageDescription());
    }

    private static function getFactory(): WidgetConfigFactoryInterface
    {
        $translator = new /**
         * @method string getLocale()
         */ class() implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
            {
                return 'usage description';
            }
        };

        return new WidgetConfigFactory(new Factory(WidgetConfig::class), self::getLocaleRepository(), $translator);
    }

    private static function getLocaleRepository(): RepositoryInterface
    {
        return new class() implements RepositoryInterface {
            public function find($id)
            {
                return null;
            }

            public function findAll()
            {
                return [];
            }

            public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
            {
                return [];
            }

            public function findOneBy(array $criteria)
            {
                Assert::keyExists($criteria, 'code');
                Assert::string($criteria['code']);

                $locale = new Locale();
                $locale->setCode($criteria['code']);

                return $locale;
            }

            public function getClassName()
            {
                return Locale::class;
            }

            public function createPaginator(array $criteria = [], array $sorting = []): iterable
            {
                return [];
            }

            public function add(ResourceInterface $resource): void
            {
            }

            public function remove(ResourceInterface $resource): void
            {
            }
        };
    }
}
