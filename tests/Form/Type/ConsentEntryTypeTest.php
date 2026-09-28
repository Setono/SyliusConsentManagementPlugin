<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Form\Type\CategoryChoiceType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Provider\CategoryProviderInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class ConsentEntryTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_keeps_the_server_side_url_and_adds_necessary_categories(): void
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->setUrl('https://shop.example.com/en_US/');

        $form = $this->factory->create(ConsentEntryType::class, $consentEntry);
        $form->submit(['consentedCategories' => ['marketing']]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertSame('https://shop.example.com/en_US/', $consentEntry->getUrl());
        self::assertEqualsCanonicalizing(['necessary', 'marketing'], $consentEntry->getConsentedCategories());
    }

    /**
     * @test
     */
    public function it_does_not_let_the_client_set_the_url(): void
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->setUrl('https://shop.example.com/en_US/');

        $form = $this->factory->create(ConsentEntryType::class, $consentEntry);
        $form->submit(['consentedCategories' => ['marketing'], 'url' => 'https://evil.example']);

        self::assertFalse($form->has('url'));
        self::assertFalse($form->isValid());
        self::assertSame('https://shop.example.com/en_US/', $consentEntry->getUrl());
    }

    protected function getExtensions(): array
    {
        $categories = [$this->createCategory('necessary', true), $this->createCategory('marketing', false)];

        $categoryProvider = $this->prophesize(CategoryProviderInterface::class);
        $categoryProvider->getCategories()->willReturn($categories);

        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn($categories);

        return [
            new PreloadedExtension([
                new ConsentEntryType($categoryProvider->reveal(), ConsentEntry::class),
                new CategoryChoiceType($categoryRepository->reveal()),
            ], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    private function createCategory(string $code, bool $necessary): Category
    {
        $category = new Category();
        $category->setCurrentLocale('en_US');
        $category->setFallbackLocale('en_US');
        $category->setCode($code);
        $category->setName(ucfirst($code));
        $category->setNecessary($necessary);

        return $category;
    }
}
