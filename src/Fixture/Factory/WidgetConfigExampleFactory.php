<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Fixture\Factory;

use Faker\Factory;
use Faker\Generator;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Bundle\CoreBundle\Fixture\OptionsResolver\LazyOption;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/* not final */ class WidgetConfigExampleFactory extends AbstractExampleFactory
{
    protected FactoryInterface $widgetConfigFactory;

    protected ChannelRepositoryInterface $channelRepository;

    protected RepositoryInterface $localeRepository;

    protected Generator $faker;

    protected OptionsResolver $optionsResolver;

    public function __construct(
        FactoryInterface $widgetConfigFactory,
        ChannelRepositoryInterface $channelRepository,
        RepositoryInterface $localeRepository,
    ) {
        $this->widgetConfigFactory = $widgetConfigFactory;
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;

        $this->faker = Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): WidgetConfigInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var WidgetConfigInterface $widgetConfig */
        $widgetConfig = $this->widgetConfigFactory->createNew();
        if (array_key_exists('usage_description', $options)) {
            Assert::string($options['usage_description']);
            $widgetConfig->setUsageDescription($options['usage_description']);
        }

        if (array_key_exists('channel', $options)) {
            Assert::isInstanceOf($options['channel'], ChannelInterface::class);
            $widgetConfig->setChannel($options['channel']);
        }

        if (array_key_exists('locale', $options)) {
            Assert::isInstanceOf($options['locale'], LocaleInterface::class);
            $widgetConfig->setLocale($options['locale']);
        }

        return $widgetConfig;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('usage_description', function (Options $options): string {
                return $this->faker->paragraph;
            })

            ->setDefault('channel', LazyOption::randomOne($this->channelRepository))
            ->setAllowedTypes('channel', ['null', 'string', ChannelInterface::class])
            ->setNormalizer('channel', LazyOption::findOneBy($this->channelRepository, 'code'))

            ->setDefault('locale', function (Options $options): LocaleInterface {
                /** @var ChannelInterface|mixed $channel */
                $channel = $options['channel'];
                Assert::isInstanceOf($channel, ChannelInterface::class);

                $defaultLocale = $channel->getDefaultLocale();
                if (null !== $defaultLocale) {
                    return $defaultLocale;
                }

                if ($channel->getLocales()->isEmpty()) {
                    throw new \InvalidArgumentException(sprintf(
                        'You have no locales at the channel "%s".',
                        (string) $channel->getCode(),
                    ));
                }

                /** @var LocaleInterface $locale */
                $locale = $this->faker->randomElement($channel->getLocales()->toArray());

                return $locale;
            })
            ->setAllowedTypes('locale', ['string', LocaleInterface::class])
            ->setNormalizer('locale', LazyOption::findOneBy($this->localeRepository, 'code'))
        ;
    }
}
