<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Form\Type\LayoutType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class LayoutTypeTest extends TypeTestCase
{
    /**
     * @test
     *
     * @dataProvider provideValidValues
     */
    public function it_accepts_css_values(string $value): void
    {
        $form = $this->factory->create(LayoutType::class, null, ['validation_groups' => ['setono_sylius_consent_management']]);
        $form->submit(['backgroundColor' => $value]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidValues
     */
    public function it_rejects_values_that_could_break_out_of_the_style_tag(string $value): void
    {
        $form = $this->factory->create(LayoutType::class, null, ['validation_groups' => ['setono_sylius_consent_management']]);
        $form->submit(['backgroundColor' => $value]);

        self::assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidValues(): iterable
    {
        yield 'hex' => ['#ffffff'];
        yield 'rgba' => ['rgba(0, 0, 0, 0.5)'];
        yield 'shorthand' => ['10px 20px'];
        yield 'calc' => ['calc(100% - 20px)'];
        yield 'keyword' => ['transparent'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'closing style tag' => ['#fff;}</style><script>alert(1)</script>'];
        yield 'extra declaration' => ['4px; color: red'];
        yield 'quotes' => ["url('https://evil.example')"];
        yield 'backslash escape' => ['\\3c script'];
    }

    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
