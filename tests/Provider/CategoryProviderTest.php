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
use Setono\SyliusConsentManagementPlugin\Factory\CategoryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\CategoryProvider;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategoryProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CategoryRepositoryInterface> */
    private ObjectProphecy $categoryRepository;

    /** @var ObjectProphecy<CategoryFactoryInterface> */
    private ObjectProphecy $categoryFactory;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $this->categoryRepository->getClassName()->willReturn(Category::class);

        $this->categoryFactory = $this->prophesize(CategoryFactoryInterface::class);
        $this->categoryFactory->createWithData(Argument::type('string'), Argument::type('array'), Argument::type('bool'))->will(
            static function (array $arguments): Category {
                $code = $arguments[0];
                self::assertIsString($code);

                $category = new Category();
                $category->setCode($code);

                return $category;
            },
        );
    }

    /**
     * @test
     */
    public function it_creates_the_default_categories_when_none_exist(): void
    {
        $this->categoryRepository->findAll()->willReturn([]);
        $this->categoryRepository->add(Argument::type(Category::class))->shouldBeCalledTimes(4);

        $codes = array_map(static fn (CategoryInterface $category): ?string => $category->getCode(), $this->createProvider()->getCategories());

        self::assertEqualsCanonicalizing(['functional', 'marketing', 'statistical', 'necessary'], $codes);
    }

    /**
     * @test
     */
    public function it_returns_the_categories_created_by_a_concurrent_request(): void
    {
        $concurrentlyCreatedCategories = [new Category(), new Category()];

        $this->categoryRepository->findAll()->willReturn([], $concurrentlyCreatedCategories);
        $this->categoryRepository->add(Argument::type(Category::class))->willThrow(new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
        }, null));

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Category::class)->willReturn($entityManager);
        $managerRegistry->getManagers()->willReturn(['default' => $entityManager]);
        $managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($entityManager);

        self::assertSame($concurrentlyCreatedCategories, $this->createProvider($managerRegistry->reveal())->getCategories());
    }

    private function createProvider(?ManagerRegistry $managerRegistry = null): CategoryProvider
    {
        $localeRepository = $this->prophesize(RepositoryInterface::class);
        $localeRepository->findAll()->willReturn([]);

        return new CategoryProvider(
            $this->categoryRepository->reveal(),
            $this->categoryFactory->reveal(),
            $localeRepository->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $managerRegistry,
        );
    }
}
