<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\Service;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ServiceType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'setono_sylius_consent_management.form.service.category',
                'placeholder' => 'setono_sylius_consent_management.form.service.category_placeholder',
                'choices' => Service::getCategories(),
                'choice_label' => static function (string $choice, string $key, string $value): string {
                    return 'setono_sylius_consent_management.form.service.categories.' . $key;
                },
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => ServiceTranslationType::class,
                'label' => 'setono_sylius_consent_management.form.service.translations',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_service';
    }
}
