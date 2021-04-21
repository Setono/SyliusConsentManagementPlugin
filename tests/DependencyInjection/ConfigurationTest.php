<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\Configuration;
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
            'The child config "notify" under "setono_sylius_consent_management" must be configured: A list of emails to notify when a new cookie is discovered'
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
                        'factory' => Factory::class,
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
            ],
            'sample_rate' => 0.01,
            'notify' => [
                'johndoe@example.com',
            ],
        ]);
    }
}
