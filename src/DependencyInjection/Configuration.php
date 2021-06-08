<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ConsentEntryRepository;
use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\CookieRepository;
use Setono\SyliusConsentManagementPlugin\Doctrine\ORM\ServiceRepository;
use Setono\SyliusConsentManagementPlugin\Form\Type\CookieType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
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

        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress PossiblyUndefinedMethod
         * @psalm-suppress PossiblyNullReference
         */
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('driver')
                    ->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)
                ->end()
                ->arrayNode('notify')
                    ->requiresAtLeastOneElement()
                    ->isRequired()
                    ->info('A list of emails to notify when a new cookie is discovered')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('crawling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('pages')
                            ->defaultValue(100)
                            ->info('The number of pages to crawl per channel when running setono:sylius-consent-management:crawl. Notice that all pages will be crawled twice. Once without consent and once with consent.')
                            ->example(500)
                            ->min(1)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sampling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->floatNode('rate')
                            ->defaultValue(0.01)
                            ->info('The rate at which cookies should be sampled/collected from requests. The default is every 100th request')
                            ->example(0.1)
                            ->min(0.0001)
                            ->max(1)
                        ->end()
                        ->arrayNode('firewalls')
                            ->defaultValue(['shop'])
                            ->info("A list of firewall to sample. The default is to only sample the 'shop' firewall. This means you won't sample API requests and admin requests by default.")
                            ->scalarPrototype()->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress PossiblyUndefinedMethod
         * @psalm-suppress PossiblyNullReference
         */
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
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
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
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
                    ->end()
                ->end()
            ->end()
        ;
    }
}
