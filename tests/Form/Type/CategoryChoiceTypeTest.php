<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Form\Type\CategoryChoiceType;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class CategoryChoiceTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_disables_necessary_categories_for_any_category_implementation(): void
    {
        // Prophecy doubles implement CategoryInterface without extending the default Category model,
        // just like a custom model configured via resources.category.classes.model
        $form = $this->factory->create(CategoryChoiceType::class, null, [
            'multiple' => true,
            'expanded' => true,
        ]);

        $view = $form->createView();

        // FormView::$vars is untyped
        /** @var array{necessary: list<string>} $vars */
        $vars = $view->vars;
        /** @var array{disabled: bool} $necessaryVars */
        $necessaryVars = $view->children[0]->vars;
        /** @var array{disabled: bool} $marketingVars */
        $marketingVars = $view->children[1]->vars;

        self::assertSame(['necessary'], $vars['necessary']);
        self::assertTrue($necessaryVars['disabled']);
        self::assertFalse($marketingVars['disabled']);
    }

    protected function getExtensions(): array
    {
        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([
            $this->category('necessary', true),
            $this->category('marketing', false),
        ]);

        return [
            new PreloadedExtension([new CategoryChoiceType($categoryRepository->reveal())], []),
        ];
    }

    private function category(string $code, bool $necessary): CategoryInterface
    {
        $category = $this->prophesize(CategoryInterface::class);
        $category->getCode()->willReturn($code);
        $category->getName()->willReturn(ucfirst($code));
        $category->isNecessary()->willReturn($necessary);

        return $category->reveal();
    }
}
