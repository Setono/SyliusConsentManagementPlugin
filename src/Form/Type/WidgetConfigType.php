<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\LocaleBundle\Form\Type\LocaleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class WidgetConfigType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'sylius.ui.channel',
            ])
            ->add('locale', LocaleChoiceType::class, [
                'label' => 'sylius.ui.locale',
            ])
            ->add('heading', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.widget_config.heading',
            ])
            ->add('body', TextareaType::class, [
                'label' => 'setono_sylius_consent_management.form.widget_config.body',
                'help' => 'setono_sylius_consent_management.form.widget_config.body_help',
            ])
            ->add('acceptSelectedButtonLabel', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.widget_config.accept_selected_button_label',
            ])
            ->add('acceptAllButtonLabel', TextType::class, [
                'label' => 'setono_sylius_consent_management.form.widget_config.accept_all_button_label',
            ])
            ->add('layout', LayoutType::class)
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_widget_config';
    }
}
