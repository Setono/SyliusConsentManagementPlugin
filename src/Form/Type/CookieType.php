<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
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
        // todo create service choice type instead
        $builder
            ->add('ttl', DateIntervalType::class, [
                'label' => 'setono_sylius_consent_management.form.cookie.ttl',
                'with_hours' => true,
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
                'label' => 'sylius.ui.translations',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_cookie';
    }
}
