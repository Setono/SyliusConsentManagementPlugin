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
    public function it_ignores_the_url_posted_by_pages_rendered_before_it_was_removed(): void
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->setUrl('https://shop.example.com/en_US/');

        $form = $this->factory->create(ConsentEntryType::class, $consentEntry);
        $form->submit(['consentedCategories' => ['marketing'], 'url' => 'https://evil.example']);

        self::assertFalse($form->has('url'));
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertSame('https://shop.example.com/en_US/', $consentEntry->getUrl());
        self::assertEqualsCanonicalizing(['necessary', 'marketing'], $consentEntry->getConsentedCategories());
    }

    /**
     * @test
     *
     * @dataProvider provideMalformedData
     */
    public function it_is_invalid_when_the_submitted_data_is_malformed(array|string $data): void
    {
        $form = $this->factory->create(ConsentEntryType::class, new ConsentEntry());
        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{array|string}>
     */
    public static function provideMalformedData(): iterable
    {
        yield 'a string instead of the form' => ['marketing'];
        yield 'a string instead of the categories' => [['consentedCategories' => 'marketing']];
        yield 'a nested array among the categories' => [['consentedCategories' => ['marketing', ['functional']]]];
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
