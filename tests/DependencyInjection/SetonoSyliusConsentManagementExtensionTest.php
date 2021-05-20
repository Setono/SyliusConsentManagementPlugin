<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\SetonoSyliusConsentManagementExtension;

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
}
