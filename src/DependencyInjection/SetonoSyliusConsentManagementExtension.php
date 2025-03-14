<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\UrlProviderInterface;
use Setono\SyliusConsentManagementPlugin\Workflow\CookieWorkflow;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;

final class SetonoSyliusConsentManagementExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     widget: array{cookie_name: string, cookie_value: string},
         *     crawler: array{options: array, url_provider: array{tracking_url_patterns: array<string, string>}},
         *     sampling: array{rate: float, firewalls: list<string>},
         *     notify: list<string>,
         *     resources: array<string, mixed>
         * } $config
         *
         * @psalm-suppress PossiblyNullArgument
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        self::configureWidget($container, $config['widget']);
        self::configureCrawler($container, $config['crawler']);

        $container->setParameter('setono_sylius_consent_management.sampling.rate', $config['sampling']['rate']);
        $container->setParameter('setono_sylius_consent_management.sampling.firewalls', $config['sampling']['firewalls']);
        $container->setParameter('setono_sylius_consent_management.notify', $config['notify']);

        $container
            ->registerForAutoconfiguration(WidgetDisplayDeciderInterface::class)
            ->addTag('setono_sylius_consent_management.widget_display_decider')
        ;

        $this->registerResources(
            'setono_sylius_consent_management',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.xml');
    }

    /**
     * @param array{cookie_name: string, cookie_value: string} $config
     */
    private static function configureWidget(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('setono_sylius_consent_management.widget.cookie_name', $config['cookie_name']);
        $container->setParameter('setono_sylius_consent_management.widget.cookie_value', $config['cookie_value']);
    }

    /**
     * @param array{options: array, url_provider: array{tracking_url_patterns: array}} $config
     */
    private static function configureCrawler(ContainerBuilder $container, array $config): void
    {
        Assert::allStringNotEmpty(array_keys($config['url_provider']['tracking_url_patterns']), 'The crawler.url_provider.tracking_url_patterns is a key value array where the key is the tracking parameter name and the value is the tracking parameter value.');

        $config['url_provider']['tracking_url_patterns'] = array_filter(array_merge([
            'utm_source' => 'google',
            // Google click id
            'gclid' => 'Cj0KCQiAz6q-BhCfARIsAOezPxnowCvBkAXGO-VaF5BBognk98ki1ZCADOEIvW7mXugI3WEk88drYcoaAh8bEALw_wcB',
            // Microsoft click id
            'msclkid' => 'ba84442c382915e41ea923e6947fb257',
            // Facebook click id
            'fbclid' => 'IwY2xjawI3i8BleHRuA2FlbQEwAGFkaWQBqxgW86CFQwEdW3LdyPyHuzbp9y3lDXT5IJLpTJlWdZtonH7PB0os5CmUSulCzchR-koK_aem_aZB74XyFCdBR4PYU3ehAiw',
            // X / Twitter click id
            'twclid' => '211hvb1a5dsvhhzlxtv3yt7prm',
            // Instagram share id
            'igshid' => 'MzRlODBiNWFlZA==',
            // TikTok click id
            'ttclid' => 'E_C_P_CrMBC4ZxGk6tRXjjbm0UaFJuYgk_Q2Apz2cxWXKnPuoGiclBo4os6EX9U0-QcKg-1P3PNznMewI6G-gROCH8QToaXp9_WBxqpipD6EJmVZ6XhS4uFl9pFxPGdEwhEW4F7l5SAurguRr7GkRy4kwu9gjfHAQqT0hkuYX_joL4cStfCN5fz3KuOdyoP9AcA39allU5hjfVmm5tDATpKPt90vO1Ckl11jBmq1NydPeiceOLzr7ElXwSBHYyLjA',
            // Partner id - often used in affiliate marketing
            'pid' => 'wake-me-up',
            // Affiliate id
            'aff_id' => '123456789',
            // General tracking parameter
            'ref' => 'google',
        ], $config['url_provider']['tracking_url_patterns']));

        $container->setParameter('setono_sylius_consent_management.crawler.options', $config['options']);
        $container->setParameter('setono_sylius_consent_management.crawler.url_provider.tracking_url_patterns', $config['url_provider']['tracking_url_patterns']);

        $container
            ->registerForAutoconfiguration(UrlProviderInterface::class)
            ->addTag('setono_sylius_consent_management.url_provider')
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'workflows' => CookieWorkflow::getConfig(),
        ]);

        $container->prependExtensionConfig('sylius_grid', [
            'templates' => [
                'bulk_action' => [
                    'bump_last_seen_at' => '@SetonoSyliusConsentManagementPlugin/admin/grid/bulk_action/bump_last_seen_at.html.twig',
                ],
                'filter' => [
                    'stale' => '@SetonoSyliusConsentManagementPlugin/admin/grid/filter/stale.html.twig',
                ],
            ],
            'grids' => [
                'setono_sylius_consent_management_admin_category' => [
                    'driver' => [
                        'options' => [
                            'class' => '%setono_sylius_consent_management.model.category.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'sorting' => [
                        'position' => 'asc',
                    ],
                    'fields' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.name',
                        ],
                        'necessary' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_consent_management.ui.necessary',
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/yesNo.html.twig',
                            ],
                            'sortable' => null,
                        ],
                        'position' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.position',
                            'sortable' => null,
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
                'setono_sylius_consent_management_admin_consent_entry' => [
                    'driver' => [
                        'options' => [
                            'class' => '%setono_sylius_consent_management.model.consent_entry.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'clientId' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.client_id',
                        ],
                        'url' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.url',
                        ],
                        'ip' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.ip',
                        ],
                        'userAgent' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.user_agent',
                        ],
                        'consentedCategories' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_consent_management.ui.consented_categories',
                            'options' => [
                                'template' => '@SetonoSyliusConsentManagementPlugin/admin/grid/field/consented_categories.html.twig',
                            ],
                        ],
                        'createdAt' => [
                            'type' => 'datetime',
                            'label' => 'sylius.ui.created_at',
                        ],
                        'updatedAt' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_consent_management.ui.updated_at',
                        ],
                    ],
                    'filters' => [
                        'search' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.consent_entry_search',
                            'options' => [
                                'fields' => ['clientId', 'ip'],
                            ],
                        ],
                    ],
                    'actions' => [
                        'item' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
                'setono_sylius_consent_management_admin_cookie' => [
                    'driver' => [
                        'options' => [
                            'class' => '%setono_sylius_consent_management.model.cookie.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.name',
                        ],
                        'url' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_consent_management.ui.url',
                            'options' => [
                                'template' => '@SetonoSyliusConsentManagementPlugin/admin/grid/field/url.html.twig',
                            ],
                        ],
                        'service' => [
                            'type' => 'twig',
                            'path' => '.',
                            'label' => 'setono_sylius_consent_management.ui.service',
                            'options' => [
                                'template' => '@SetonoSyliusConsentManagementPlugin/admin/grid/field/service.html.twig',
                            ],
                        ],
                        'ttl' => [
                            'type' => 'twig',
                            'path' => '.',
                            'label' => 'setono_sylius_consent_management.ui.ttl',
                            'options' => [
                                'template' => '@SetonoSyliusConsentManagementPlugin/admin/grid/field/ttl.html.twig',
                            ],
                        ],
                        'session' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_consent_management.ui.session',
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/yesNo.html.twig',
                            ],
                            'sortable' => null,
                        ],
                        'state' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.state',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/state.html.twig',
                                'vars' => [
                                    'labels' => '@SetonoSyliusConsentManagementPlugin/admin/cookie/state',
                                ],
                            ],
                        ],
                        'lastSeenAt' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_consent_management.ui.last_seen_at',
                            'sortable' => null,
                            'options' => [
                                'format' => 'Y-m-d',
                            ],
                        ],
                    ],
                    'filters' => [
                        'search' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.search',
                            'options' => [
                                'fields' => ['name'],
                            ],
                        ],
                        'state' => [
                            'type' => 'select',
                            'label' => 'sylius.ui.state',
                            'form_options' => [
                                'choices' => array_combine(
                                    array_map(static fn (string $state): string => sprintf('setono_sylius_consent_management.ui.%s', $state), Cookie::getStates()),
                                    Cookie::getStates(),
                                ),
                            ],
                            'default_value' => CookieInterface::STATE_CONFIRMED,
                        ],
                        'service' => [
                            'type' => 'entity',
                            'label' => 'setono_sylius_consent_management.ui.service',
                            'form_options' => [
                                'class' => '%setono_sylius_consent_management.model.service.class%',
                            ],
                        ],
                        'session' => [
                            'type' => 'boolean',
                            'label' => 'setono_sylius_consent_management.ui.session',
                        ],
                        'stale' => [
                            'type' => 'stale',
                            'label' => 'setono_sylius_consent_management.ui.stale',
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                        'bulk' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                            'bump_last_seen_at' => [
                                'type' => 'bump_last_seen_at',
                                'label' => 'setono_sylius_consent_management.ui.bump_last_seen_at',
                            ],
                        ],
                    ],
                ],
                'setono_sylius_consent_management_admin_service' => [
                    'driver' => [
                        'options' => [
                            'class' => '%setono_sylius_consent_management.model.service.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'code' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.code',
                            'sortable' => null,
                        ],
                        'name' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.name',
                            'sortable' => 'translation.name',
                        ],
                        'category' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.category',
                        ],
                    ],
                    'filters' => [
                        'search' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_consent_management.ui.service_search',
                            'options' => [
                                'fields' => ['code', 'translation.name'],
                            ],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
                'setono_sylius_consent_management_admin_widget_config' => [
                    'driver' => [
                        'options' => [
                            'class' => '%setono_sylius_consent_management.model.widget_config.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'channel' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.channel',
                            'options' => [
                                'template' => '@SyliusAdmin/Order/Grid/Field/channel.html.twig',
                            ],
                        ],
                        'locale' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.locale',
                            'options' => [
                                'template' => '@SyliusAdmin/Locale/Grid/Field/name.html.twig',
                            ],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('sylius_mailer', [
            'emails' => [
                'setono_sylius_consent_management__new_cookies' => [
                    'subject' => 'setono_sylius_consent_management.emails.new_cookies.subject',
                    'template' => '@SetonoSyliusConsentManagementPlugin/email/new_cookies.html.twig',
                ],
                'setono_sylius_consent_management__stale_cookies' => [
                    'subject' => 'setono_sylius_consent_management.emails.stale_cookies.subject',
                    'template' => '@SetonoSyliusConsentManagementPlugin/email/stale_cookies.html.twig',
                ],
            ],
        ]);

        $container->prependExtensionConfig('sylius_ui', [
            'events' => [
                'setono_sylius_consent_management.admin.widget_config.index' => [
                    'blocks' => [
                        'index_header' => [
                            'template' => '@SetonoSyliusConsentManagementPlugin/admin/widget_config/index_header.html.twig',
                            'priority' => 15,
                        ],
                    ],
                ],
                'sylius.shop.layout.stylesheets' => [
                    'blocks' => [
                        'setono_sylius_consent_management.stylesheets.widget' => [
                            'template' => '@SyliusUi/_stylesheets.html.twig',
                            'context' => [
                                'path' => 'bundles/setonosyliusconsentmanagementplugin/css/widget.css',
                            ],
                        ],
                    ],
                ],
                'sylius.shop.layout.javascripts' => [
                    'blocks' => [
                        'setono_sylius_consent_management.javascripts.manager' => [
                            'template' => '@SyliusUi/_javascripts.html.twig',
                            'context' => [
                                'path' => 'bundles/setonosyliusconsentmanagementplugin/js/manager.js',
                            ],
                        ],
                        'setono_sylius_consent_management.javascripts.categories' => [
                            'template' => '@SetonoSyliusConsentManagementPlugin/shop/javascripts/categories.html.twig',
                        ],
                        'setono_sylius_consent_management.javascripts.widget' => [
                            'template' => '@SetonoSyliusConsentManagementPlugin/shop/javascripts/widget.html.twig',
                        ],
                    ],
                ],
                'setono_sylius_consent_management.admin.cookie.update.javascripts' => [
                    'blocks' => [
                        'setono_sylius_consent_management.javascripts' => [
                            'template' => '@SetonoSyliusConsentManagementPlugin/admin/cookie/_javascripts.html.twig',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
