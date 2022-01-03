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
         * @var array{
         *     sampling: array{enabled: bool, rate: float, firewalls: array<array-key, string>},
         *     notify: array<array-key, string>,
         *     driver: string, resources: array<string, mixed>
         * } $config
         * @psalm-suppress PossiblyNullArgument
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_consent_management.sampling.enabled', $config['sampling']['enabled']);
        $container->setParameter('setono_sylius_consent_management.sampling.rate', $config['sampling']['rate']);
        $container->setParameter('setono_sylius_consent_management.sampling.firewalls', $config['sampling']['firewalls']);
        $container->setParameter('setono_sylius_consent_management.notify', $config['notify']);

        $this->registerResources('setono_sylius_consent_management', $config['driver'], $config['resources'], $container);

        $loader->load('services.xml');

        if ($config['sampling']['enabled']) {
            $loader->load('services/conditional/sample_cookies.xml');
        }
    }
}
