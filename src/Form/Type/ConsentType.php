<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Controller\Action\ConsentCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preferencesGranted', CheckboxType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.preferences',
                'required' => false,
            ])
            ->add('statisticsGranted', CheckboxType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.statistics',
                'required' => false,
            ])
            ->add('marketingGranted', CheckboxType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.marketing',
                'required' => false,
            ])
            ->add('buttonMoreInformation', ButtonType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.buttons.more_information',
                'attr' => [
                    'class' => 'sscm-btn-more-information'
                ]
            ])
            ->add('buttonAccept', SubmitType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.buttons.accept',
            ])
            ->add('buttonSave', SubmitType::class, [
                'label' => 'setono_sylius_consent_management.form.consent.buttons.save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConsentCommand::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_consent';
    }
}
