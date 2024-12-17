<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CategoryChoiceType extends AbstractType
{
    public function __construct(private readonly string $categoryClass)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => $this->categoryClass,
            'choice_label' => 'name',
            'label' => 'setono_sylius_consent_management.form.category_choice.label',
            'placeholder' => 'setono_sylius_consent_management.form.category_choice.placeholder',
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
