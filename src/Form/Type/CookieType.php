<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
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
            ])
            ->add('service', EntityType::class, [
                'label' => 'setono_sylius_consent_management.form.cookie.service',
                'placeholder' => 'setono_sylius_consent_management.form.cookie.service_placeholder',
                'class' => $this->serviceClass,
                'choice_label' => 'name',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_cookie';
    }
}
