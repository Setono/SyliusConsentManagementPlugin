<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
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

        $category1 = new Category();
        $category1->setCode(DefaultConsents::CONSENT_FUNCTIONAL);

        $category2 = new Category();
        $category2->setCode(DefaultConsents::CONSENT_STATISTICAL);

        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([$category1, $category2]);

        $runtimeLoader = new class($consentChecker, $categoryRepository->reveal()) implements RuntimeLoaderInterface {
            public function __construct(
                private readonly ConsentCheckerInterface $consentChecker,
                private readonly CategoryRepositoryInterface $categoryRepository,
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
                    $this->categoryRepository,
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
