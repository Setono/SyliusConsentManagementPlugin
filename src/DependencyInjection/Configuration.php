<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Setono\SyliusConsentManagementPlugin\Form\Type\CategoryTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\CategoryType;
use Setono\SyliusConsentManagementPlugin\Form\Type\CookieTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\CookieType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceType;
use Setono\SyliusConsentManagementPlugin\Form\Type\WidgetConfigType;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\CategoryTranslation;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieTranslation;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepository;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepository;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepository;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepository;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Resource\Factory\TranslatableFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_consent_management');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        /** @psalm-suppress MixedMethodCall,UndefinedInterfaceMethod,PossiblyUndefinedMethod,PossiblyNullReference */
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                // todo allow this to be empty and use the channel email as a fallback
                ->arrayNode('notify')
                    ->requiresAtLeastOneElement()
                    ->isRequired()
                    ->info('A list of emails to notify when a new cookie is discovered')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('sampling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->floatNode('rate')
                            ->defaultValue(0.01)
                            ->info('The rate at which cookies should be sampled/collected from requests. The default is every 100th request. If you set it to 0, you will never sample any requests. Internally the sampling uses the random_int function to determine whether it should sample, so the rate should be higher than 1/2^31 to trigger sampling.')
                            ->example('0.1')
                            ->min(0)
                            ->max(1)
                        ->end()
                        ->arrayNode('firewalls')
                            ->defaultValue(['shop'])
                            ->info("A list of firewalls to sample. The default is to only sample the 'shop' firewall. This means you won't sample API requests and admin requests by default.")
                            ->scalarPrototype()->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        /** @psalm-suppress MixedMethodCall,UndefinedInterfaceMethod,PossiblyUndefinedMethod,PossiblyNullReference */
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('category')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Category::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(CategoryRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->end()
                                        ->scalarNode('form')->defaultValue(CategoryType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                                ->arrayNode('translation')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->variableNode('options')->end()
                                        ->arrayNode('classes')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->defaultValue(CategoryTranslation::class)->cannotBeEmpty()->end()
                                                ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                                ->scalarNode('repository')->cannotBeEmpty()->end()
                                                ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                                ->scalarNode('form')->defaultValue(CategoryTranslationType::class)->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('consent_entry')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(ConsentEntry::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ConsentEntryRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('cookie')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Cookie::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(CookieRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(CookieType::class)->end()
                                        ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('translation')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->variableNode('options')->end()
                                        ->arrayNode('classes')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->defaultValue(CookieTranslation::class)->cannotBeEmpty()->end()
                                                ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                                ->scalarNode('repository')->cannotBeEmpty()->end()
                                                ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                                ->scalarNode('form')->defaultValue(CookieTranslationType::class)->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('service')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Service::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ServiceRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->end()
                                        ->scalarNode('form')->defaultValue(ServiceType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                                ->arrayNode('translation')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->variableNode('options')->end()
                                        ->arrayNode('classes')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->defaultValue(ServiceTranslation::class)->cannotBeEmpty()->end()
                                                ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                                ->scalarNode('repository')->cannotBeEmpty()->end()
                                                ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                                ->scalarNode('form')->defaultValue(ServiceTranslationType::class)->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widget_config')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(WidgetConfig::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(WidgetConfigRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(WidgetConfigType::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
        ;
    }
}
