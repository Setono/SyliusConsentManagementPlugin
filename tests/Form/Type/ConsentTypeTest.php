<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Controller\ConsentCommand;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Form\Type\ConsentType
 *
 * See https://symfony.com/doc/current/form/unit_testing.html
 */
final class ConsentTypeTest extends TypeTestCase
{
    /**
     * @test
     */
    public function submit_valid_data(): void
    {
        $model = new ConsentCommand();
        $form = $this->factory->create(ConsentType::class, $model);

        $form->submit([
            'preferences' => false,
            'marketing' => false,
            'statistics' => false,
        ]);

        self::assertTrue($form->isSynchronized());

        $expected = new ConsentCommand();
        $expected->preferencesGranted = $expected->marketingGranted = $expected->statisticsGranted = false;

        self::assertEquals($expected, $model);
    }
}
