<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusConsentManagementExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{sample_rate: float, notify: array<array-key, string>, driver: string, resources: array<string, mixed>} $config
         * @psalm-suppress PossiblyNullArgument
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_consent_management.sample_rate', $config['sample_rate']);
        $container->setParameter('setono_sylius_consent_management.notify', $config['notify']);

        $this->registerResources('setono_sylius_consent_management', $config['driver'], $config['resources'], $container);

        $loader->load('services.xml');
    }
}
