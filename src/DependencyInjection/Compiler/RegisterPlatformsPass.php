<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPlatformsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('setono_sylius_consent_management.platform.registry')) {
            return;
        }

        $platformRegistry = $container->getDefinition('setono_sylius_consent_management.platform.registry');

        /**
         * @var string $id
         * @var array $tags
         */
        foreach ($container->findTaggedServiceIds('setono_sylius_consent_management.platform') as $id => $tags) {
            $platformRegistry->addArgument(new Reference($id));
        }
    }
}
