<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Provider\ConsentedCategoriesProvider;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;

final class ConsentedCategoriesProviderTest extends TestCase
{
    use ProphecyTrait;

    private ConsentedCategoriesProvider $provider;

    protected function setUp(): void
    {
        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([
            self::createCategory(DefaultConsents::CONSENT_FUNCTIONAL),
            self::createCategory(DefaultConsents::CONSENT_MARKETING),
            self::createCategory(DefaultConsents::CONSENT_STATISTICAL),
        ]);

        $this->provider = new ConsentedCategoriesProvider($categoryRepository->reveal(), new StaticConsentChecker([
            DefaultConsents::CONSENT_STATISTICAL => true,
        ]));
    }

    /**
     * @test
     */
    public function it_returns_list(): void
    {
        self::assertSame([DefaultConsents::CONSENT_STATISTICAL], $this->provider->getCategories());
    }

    /**
     * @test
     */
    public function it_returns_array(): void
    {
        self::assertSame([
            DefaultConsents::CONSENT_FUNCTIONAL => false,
            DefaultConsents::CONSENT_MARKETING => false,
            DefaultConsents::CONSENT_STATISTICAL => true,
        ], $this->provider->getCategories(true));
    }

    private static function createCategory(string $code): Category
    {
        $category = new Category();
        $category->setCode($code);

        return $category;
    }
}
