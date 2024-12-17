<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class CookieType extends AbstractResourceType
{
    /**
     * @param list<string> $validationGroups
     */
    public function __construct(
        string $dataClass,
        /** @var class-string<ServiceInterface> $serviceClass */
        private readonly string $serviceClass,
        array $validationGroups = [],
    ) {
        parent::__construct($dataClass, $validationGroups);
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
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_cookie';
    }
}
