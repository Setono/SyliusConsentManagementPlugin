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

    /**
     * @test
     */
    public function container_has_parameters(): void
    {
        $this->load();

        /** @var array<string, mixed> $resources */
        $resources = $this->container->getParameter('sylius.resources');

        self::assertArrayHasKey('setono_sylius_consent_management.consent_entry', $resources);
        self::assertArrayHasKey('setono_sylius_consent_management.service', $resources);
        self::assertArrayHasKey('setono_sylius_consent_management.service_translation', $resources);
    }
}
