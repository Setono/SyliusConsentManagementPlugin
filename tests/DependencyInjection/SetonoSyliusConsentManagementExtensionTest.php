<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusConsentManagementPlugin\Checker\CachedConsentChecker;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\CachedWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\SetonoSyliusConsentManagementExtension;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler\SaveCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Workflow\NotifyAboutCookiesSubscriber;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyDependencyInjectionTest
 */
final class SetonoSyliusConsentManagementExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusConsentManagementExtension(),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return [
            'notify' => [
                'johndoe@example.com',
            ],
        ];
    }

    /**
     * @test
     */
    public function container_has_parameters(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_consent_management.sampling.rate', 0.01);
        $this->assertContainerBuilderHasParameter('setono_sylius_consent_management.sampling.firewalls', ['shop']);
        $this->assertContainerBuilderHasParameter('setono_sylius_consent_management.notify', [
            'johndoe@example.com',
        ]);

        /** @var array<string, mixed> $resources */
        $resources = $this->container->getParameter('sylius.resources');

        self::assertArrayHasKey('setono_sylius_consent_management.consent_entry', $resources);
        self::assertArrayHasKey('setono_sylius_consent_management.cookie', $resources);
        self::assertArrayHasKey('setono_sylius_consent_management.service', $resources);
        self::assertArrayHasKey('setono_sylius_consent_management.service_translation', $resources);
    }

    /**
     * @test
     */
    public function it_configures_the_sampling_rate_limiters(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_consent_management.sampling.rate_limit.recordings', [
            'id' => 'setono_sylius_consent_management_sampling_recordings',
            'policy' => 'sliding_window',
            'limit' => 10,
            'interval' => '1 hour',
        ]);
        $this->assertContainerBuilderHasParameter('setono_sylius_consent_management.sampling.rate_limit.new_cookies', [
            'id' => 'setono_sylius_consent_management_sampling_new_cookies',
            'policy' => 'sliding_window',
            'limit' => 10,
            'interval' => '1 day',
        ]);
    }

    /**
     * @test
     */
    public function it_lets_the_sampling_rate_limiters_accept_everything_when_disabled(): void
    {
        $this->load(['sampling' => ['rate_limit' => false]]);

        foreach (['recordings', 'new_cookies'] as $limit) {
            $config = $this->container->getParameter('setono_sylius_consent_management.sampling.rate_limit.' . $limit);
            self::assertIsArray($config);
            self::assertSame('no_limit', $config['policy']);
        }
    }

    /**
     * @test
     */
    public function it_resets_services_with_request_scoped_state_between_requests(): void
    {
        $this->load();

        foreach ([
            CachedConsentChecker::class,
            CachedWidgetDisplayDecider::class,
            NotifyAboutCookiesSubscriber::class,
            SaveCookiesSubscriber::class,
        ] as $id) {
            $this->assertContainerBuilderHasServiceDefinitionWithTag($id, 'kernel.reset', ['method' => 'reset']);
        }
    }
}
