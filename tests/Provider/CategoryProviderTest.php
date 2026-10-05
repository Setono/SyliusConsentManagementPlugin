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

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

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

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->entityManager->persist(Argument::type(Category::class))->willReturn();

        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(Category::class)->willReturn($this->entityManager->reveal());
        $this->managerRegistry->getManagers()->willReturn(['default' => $this->entityManager->reveal()]);
    }

    /**
     * @test
     */
    public function it_creates_the_default_categories_in_a_single_flush(): void
    {
        $this->categoryRepository->findAll()->willReturn([]);
        $this->categoryRepository->add(Argument::any())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::type(Category::class))->shouldBeCalledTimes(4);
        $this->entityManager->flush()->shouldBeCalledOnce();

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
        $this->entityManager->flush()->willThrow(self::createUniqueConstraintViolationException());
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($this->entityManager->reveal());

        self::assertSame($concurrentlyCreatedCategories, $this->createProvider()->getCategories());
    }

    /**
     * @test
     */
    public function it_rethrows_when_no_categories_created_by_a_concurrent_request_are_found(): void
    {
        $exception = self::createUniqueConstraintViolationException();

        $this->categoryRepository->findAll()->willReturn([]);
        $this->entityManager->flush()->willThrow($exception);
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($this->entityManager->reveal());

        $this->expectExceptionObject($exception);

        $this->createProvider()->getCategories();
    }

    /**
     * @test
     */
    public function it_rethrows_when_the_manager_cannot_be_reset(): void
    {
        $exception = self::createUniqueConstraintViolationException();

        $this->categoryRepository->findAll()->shouldBeCalledOnce()->willReturn([]);
        $this->entityManager->flush()->willThrow($exception);
        $this->managerRegistry->getManagers()->willReturn([]);
        $this->managerRegistry->resetManager(Argument::any())->shouldNotBeCalled();

        $this->expectExceptionObject($exception);

        $this->createProvider()->getCategories();
    }

    private function createProvider(): CategoryProvider
    {
        $localeRepository = $this->prophesize(RepositoryInterface::class);
        $localeRepository->findAll()->willReturn([]);

        return new CategoryProvider(
            $this->categoryRepository->reveal(),
            $this->categoryFactory->reveal(),
            $localeRepository->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->managerRegistry->reveal(),
        );
    }

    private static function createUniqueConstraintViolationException(): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
        }, null);
    }
}
