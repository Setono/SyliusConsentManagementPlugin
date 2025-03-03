<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The children of this form are by default rendered as CSS variables in the frontend.
 *
 * The children's names are converted to snake case and prefixed with '--sscm-widget-'.
 *
 * For example, the backgroundColor will be rendered as '--sscm-widget-background-color: <value>;'
 */
final class LayoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('backgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.background_color_placeholder'],
                'required' => false,
            ])
            ->add('maxWidth', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.max_width',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.max_width_placeholder'],
                'required' => false,
            ])
            ->add('borderRadius', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.border_radius',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.border_radius_placeholder'],
                'required' => false,
            ])
            ->add('buttonBorderRadius', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_border_radius',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_border_radius_placeholder'],
                'required' => false,
            ])
            ->add('buttonPadding', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_padding',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_padding_placeholder'],
                'required' => false,
            ])
            ->add('buttonAcceptAllColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_all_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_all_color_placeholder'],
                'required' => false,
            ])
            ->add('buttonAcceptAllBackgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_all_background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_all_background_color_placeholder'],
                'required' => false,
            ])
            ->add('buttonAcceptSelectedColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_selected_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_selected_color_placeholder'],
                'required' => false,
            ])
            ->add('buttonAcceptSelectedBackgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_selected_background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_selected_background_color_placeholder'],
                'required' => false,
            ])
        ;
    }
}
