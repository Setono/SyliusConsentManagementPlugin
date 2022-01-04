<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class CookieType extends AbstractResourceType
{
    private string $serviceClass;

    /**
     * @param class-string $serviceClass
     * @param array<array-key, string> $validationGroups
     */
    public function __construct(string $dataClass, string $serviceClass, array $validationGroups = [])
    {
        parent::__construct($dataClass, $validationGroups);

        $this->serviceClass = $serviceClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('necessary', CheckboxType::class, [
                'label' => 'setono_sylius_consent_management.form.cookie.necessary',
                'required' => false,
            ])
            ->add('service', EntityType::class, [
                'label' => 'setono_sylius_consent_management.form.cookie.service',
                'placeholder' => 'setono_sylius_consent_management.form.cookie.service_placeholder',
                'class' => $this->serviceClass,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => CookieTranslationType::class,
                'label' => 'setono_sylius_consent_management.form.cookie.translations',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_cookie';
    }
}
