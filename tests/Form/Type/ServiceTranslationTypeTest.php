<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType
 *
 * See https://symfony.com/doc/current/form/unit_testing.html
 */
final class ServiceTranslationTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new ServiceTranslationType(ServiceTranslation::class)], []),
        ];
    }

    /**
     * @test
     */
    public function submit_valid_data(): void
    {
        $model = new ServiceTranslation();
        $form = $this->factory->create(ServiceTranslationType::class, $model);

        $form->submit([
            'name' => 'name',
            'description' => 'description',
        ]);

        self::assertTrue($form->isSynchronized());

        $expected = new ServiceTranslation();
        $expected->setName('name');
        $expected->setDescription('description');

        self::assertEquals($expected, $model);
    }
}
