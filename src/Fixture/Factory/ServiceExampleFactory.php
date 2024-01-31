<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Fixture\Factory;

use Faker\Factory;
use Faker\Generator;
use Setono\Consent\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepositoryInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/* not final */ class ServiceExampleFactory extends AbstractExampleFactory
{
    protected ServiceRepositoryInterface $serviceRepository;

    protected FactoryInterface $serviceFactory;

    protected RepositoryInterface $localeRepository;

    protected Generator $faker;

    protected OptionsResolver $optionsResolver;

    public function __construct(
        ServiceRepositoryInterface $serviceRepository,
        FactoryInterface $serviceFactory,
        RepositoryInterface $localeRepository,
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->serviceFactory = $serviceFactory;
        $this->localeRepository = $localeRepository;

        $this->faker = Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): ServiceInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var ServiceInterface|null $service */
        $service = $this->serviceRepository->findOneBy(['code' => $options['code']]);
        if (null === $service) {
            /** @var ServiceInterface $service */
            $service = $this->serviceFactory->createNew();

            if (array_key_exists('code', $options)) {
                Assert::string($options['code']);
                $service->setCode($options['code']);
            }
        }

        if (array_key_exists('category', $options)) {
            Assert::string($options['category']);
            $service->setCategory($options['category']);
        }

        // add translation for each defined locales
        foreach ($this->getLocales() as $localeCode) {
            $this->createTranslation($service, $localeCode, $options);
        }

        // create or replace with custom translations
        if (array_key_exists('translations', $options)) {
            Assert::isArray($options['translations']);
            foreach ($options['translations'] as $localeCode => $translationOptions) {
                Assert::isArray($translationOptions);
                Assert::string($localeCode);
                $this->createTranslation($service, $localeCode, $translationOptions);
            }
        }

        return $service;
    }

    protected function createTranslation(ServiceInterface $service, string $localeCode, array $options = []): void
    {
        $options = $this->optionsResolver->resolve($options);

        $service->setCurrentLocale($localeCode);
        $service->setFallbackLocale($localeCode);

        if (array_key_exists('name', $options)) {
            Assert::string($options['name']);
            $service->setName($options['name']);
        }

        if (array_key_exists('description', $options)) {
            Assert::string($options['description']);
            $service->setDescription($options['description']);
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('code', function (Options $options): string {
                Assert::string($options['name']);

                return StringInflector::nameToCode($options['name']);
            })
            ->setDefault('category', function (Options $options): string {
                /** @var string $category */
                $category = $this->faker->randomElement(Consent::getAvailableConsents());

                return $category;
            })
            ->setDefault('name', function (Options $options): string {
                /** @var string $words */
                $words = $this->faker->words(3, true);

                return $words;
            })
            ->setDefault('description', function (Options $options): string {
                return $this->faker->paragraph;
            })
            ->setDefault('translations', [])
            ->setAllowedTypes('translations', ['array'])
        ;
    }

    /**
     * @return string[]
     */
    protected function getLocales(): array
    {
        return array_filter(array_map(static function (object $locale): string {
            Assert::isInstanceOf($locale, LocaleInterface::class);

            return (string) $locale->getCode();
        }, $this->localeRepository->findAll()));
    }
}
