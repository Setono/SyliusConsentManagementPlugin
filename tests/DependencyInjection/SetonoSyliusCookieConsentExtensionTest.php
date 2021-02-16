<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusCookieConsentPlugin\DependencyInjection;

use Setono\SyliusCookieConsentPlugin\DependencyInjection\SetonoSyliusCookieConsentExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyDependencyInjectionTest
 */
final class SetonoSyliusCookieConsentExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusCookieConsentExtension(),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return [
            'option' => 'option_value',
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_cookie_consent.option', 'option_value');
    }
}
