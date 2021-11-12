<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\LocaleBundle\Form\Type\LocaleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('usageDescription', TextareaType::class, [
                'label' => 'setono_sylius_consent_management.form.widget_config.usage_description',
                'required' => true,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_widget_config';
    }
}
