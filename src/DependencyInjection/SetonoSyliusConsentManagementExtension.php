<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusConsentManagementExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{sampling: array{rate: float, firewalls: array<array-key, string>}, notify: array<array-key, string>, driver: string, resources: array<string, mixed>} $config
         *
         * @psalm-suppress PossiblyNullArgument
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_consent_management.sampling.rate', $config['sampling']['rate']);
        $container->setParameter('setono_sylius_consent_management.sampling.firewalls', $config['sampling']['firewalls']);
        $container->setParameter('setono_sylius_consent_management.notify', $config['notify']);

        $this->registerResources(
            'setono_sylius_consent_management',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
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
                    ],
                    'filters' => [
                        'search' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.search',
                            'options' => [
                                'fields' => ['name'],
                            ],
                        ],
                    ],
                    'actions' => [
                        'item' => [
                            'update' => [
                                'type' => 'update',
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
                            'type' => 'twig',
                            'label' => 'setono_sylius_consent_management.ui.category',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SetonoSyliusConsentManagementPlugin/admin/grid/field/category.html.twig',
                            ],
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
                        'category' => [
                            'type' => 'select',
                            'label' => 'setono_sylius_consent_management.ui.category',
                            'form_options' => [
                                'choices' => [
                                    'setono_sylius_consent_management.ui.categories.marketing' => 'marketing',
                                    'setono_sylius_consent_management.ui.categories.preferences' => 'preferences',
                                    'setono_sylius_consent_management.ui.categories.statistics' => 'statistics',
                                ],
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
                'new_cookies' => [
                    'subject' => 'setono_sylius_consent_management.emails.new_cookies.subject',
                    'template' => '@SetonoSyliusConsentManagementPlugin/email/new_cookies.html.twig',
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
            ],
        ]);
    }
}
