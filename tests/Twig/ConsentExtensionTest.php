<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
use Setono\SyliusConsentManagementPlugin\Provider\ConsentedCategoriesProviderInterface;
use Setono\SyliusConsentManagementPlugin\Twig\ConsentExtension;
use Setono\SyliusConsentManagementPlugin\Twig\ConsentRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Test\IntegrationTestCase;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\ConsentExtension
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\ConsentRuntime
 */
final class ConsentExtensionTest extends IntegrationTestCase
{
    use ProphecyTrait;

    public function getRuntimeLoaders(): array
    {
        $consentChecker = new StaticConsentChecker([
            DefaultConsents::CONSENT_FUNCTIONAL => true,
            DefaultConsents::CONSENT_MARKETING => false,
            DefaultConsents::CONSENT_STATISTICAL => true,
        ]);

        $consentedCategoriesProvider = $this->prophesize(ConsentedCategoriesProviderInterface::class);
        $consentedCategoriesProvider->getCategories()->willReturn([DefaultConsents::CONSENT_FUNCTIONAL, DefaultConsents::CONSENT_STATISTICAL]);

        $runtimeLoader = new class($consentChecker, $consentedCategoriesProvider->reveal()) implements RuntimeLoaderInterface {
            public function __construct(
                private readonly ConsentCheckerInterface $consentChecker,
                private readonly ConsentedCategoriesProviderInterface $consentedCategoriesProvider,
            ) {
            }

            /**
             * @param string $class
             */
            public function load($class): ConsentRuntime
            {
                Assert::same($class, ConsentRuntime::class);

                return new ConsentRuntime(
                    $this->consentChecker,
                    $this->consentedCategoriesProvider,
                );
            }
        };

        return [$runtimeLoader];
    }

    public function getExtensions(): array
    {
        return [
            new ConsentExtension(),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/ConsentFixtures/';
    }
}
