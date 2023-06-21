<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\Configuration;
use Setono\SyliusConsentManagementPlugin\Form\Type\CookieTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\CookieType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceType;
use Setono\SyliusConsentManagementPlugin\Form\Type\WidgetConfigType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieTranslation;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepository;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepository;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepository;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Resource\Factory\TranslatableFactory;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyConfigTest
 */
final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @test
     */
    public function values_are_invalid_if_required_value_is_not_provided(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                [], // no values at all
            ],
            '/The child (node|config) "notify" (under|at path) "setono_sylius_consent_management" must be configured/',
            true,
        );
    }

    /**
     * @test
     */
    public function processed_value_contains_required_value(): void
    {
        $this->assertProcessedConfigurationEquals([
            [
                'notify' => ['johndoe@example.com'],
            ],
        ], [
            'driver' => 'doctrine/orm',
            'resources' => [
                'consent_entry' => [
                    'classes' => [
                        'model' => ConsentEntry::class,
                        'controller' => ResourceController::class,
                        'repository' => ConsentEntryRepository::class,
                        'form' => DefaultResourceType::class,
                        'factory' => Factory::class,
                    ],
                ],
                'cookie' => [
                    'classes' => [
                        'model' => Cookie::class,
                        'controller' => ResourceController::class,
                        'repository' => CookieRepository::class,
                        'form' => CookieType::class,
                        'factory' => TranslatableFactory::class,
                    ],
                    'translation' => [
                        'classes' => [
                            'model' => CookieTranslation::class,
                            'controller' => ResourceController::class,
                            'form' => CookieTranslationType::class,
                            'factory' => Factory::class,
                        ],
                    ],
                ],
                'service' => [
                    'classes' => [
                        'model' => Service::class,
                        'controller' => ResourceController::class,
                        'repository' => ServiceRepository::class,
                        'form' => ServiceType::class,
                        'factory' => TranslatableFactory::class,
                    ],
                    'translation' => [
                        'classes' => [
                            'model' => ServiceTranslation::class,
                            'controller' => ResourceController::class,
                            'form' => ServiceTranslationType::class,
                            'factory' => Factory::class,
                        ],
                    ],
                ],
                'widget_config' => [
                    'classes' => [
                        'model' => WidgetConfig::class,
                        'controller' => ResourceController::class,
                        'repository' => WidgetConfigRepository::class,
                        'form' => WidgetConfigType::class,
                        'factory' => Factory::class,
                    ],
                ],
            ],
            'sampling' => [
                'enabled' => true,
                'rate' => 0.01,
                'firewalls' => [
                    'shop',
                ],
            ],
            'notify' => [
                'johndoe@example.com',
            ],
        ]);
    }
}
