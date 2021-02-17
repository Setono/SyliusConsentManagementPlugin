<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Form\Type;

use Setono\SyliusCookieConsentPlugin\Controller\Action\UpdateConsentCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateConsentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preferences', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.update_consent.preferences',
                'required' => false,
            ])
            ->add('statistics', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.update_consent.statistics',
                'required' => false,
            ])
            ->add('marketing', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.update_consent.marketing',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateConsentCommand::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_cookie_consent_update_consent';
    }
}
