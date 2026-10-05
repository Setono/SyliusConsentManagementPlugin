<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Renderer\WidgetStyleRenderer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;

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
        // The values are rendered inside a <style> tag in the shop, see \Setono\SyliusConsentManagementPlugin\Renderer\WidgetStyleRenderer
        $constraints = [
            new Regex(
                pattern: WidgetStyleRenderer::SAFE_VALUE_PATTERN,
                message: 'setono_sylius_consent_management.widget_config.layout.invalid_value',
                groups: ['setono_sylius_consent_management'],
            ),
        ];

        $builder
            ->add('backgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.background_color_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('maxWidth', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.max_width',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.max_width_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('borderRadius', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.border_radius',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.border_radius_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonBorderRadius', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_border_radius',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_border_radius_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonPadding', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_padding',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_padding_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonAcceptAllColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_all_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_all_color_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonAcceptAllBackgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_all_background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_all_background_color_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonAcceptSelectedColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_selected_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_selected_color_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
            ->add('buttonAcceptSelectedBackgroundColor', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.layout.button_accept_selected_background_color',
                'attr' => ['placeholder' => 'setono_sylius_consent_management.form.layout.button_accept_selected_background_color_placeholder'],
                'required' => false,
                'constraints' => $constraints,
            ])
        ;
    }
}
