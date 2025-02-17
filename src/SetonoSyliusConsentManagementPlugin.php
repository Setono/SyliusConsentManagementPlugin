<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\CompositeWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\Compiler\RegisterPlatformsPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusConsentManagementPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeWidgetDisplayDecider::class,
            'setono_sylius_consent_management.widget_display_decider',
        ));
        $container->addCompilerPass(new RegisterPlatformsPass());

        parent::build($container);
    }

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }
}
