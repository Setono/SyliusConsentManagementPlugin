<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Form\Type;

use Setono\SyliusCookieConsentPlugin\Controller\Action\ConsentCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preferences', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.consent.preferences',
                'required' => false,
            ])
            ->add('statistics', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.consent.statistics',
                'required' => false,
            ])
            ->add('marketing', CheckboxType::class, [
                'label' => 'setono_sylius_cookie_consent.form.consent.marketing',
                'required' => false,
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
        return 'setono_sylius_cookie_consent_consent';
    }
}
