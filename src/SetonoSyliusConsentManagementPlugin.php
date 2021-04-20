<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin;

use Setono\SyliusConsentManagementPlugin\DependencyInjection\Compiler\RegisterPlatformsPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class is covered by Behat
 *
 * @codeCoverageIgnore
 */
final class SetonoSyliusConsentManagementPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
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
