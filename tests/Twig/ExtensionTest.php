<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Model\Consent;
use Setono\SyliusConsentManagementPlugin\Twig\Extension;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Test\IntegrationTestCase;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Extension
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Runtime
 */
final class ExtensionTest extends IntegrationTestCase
{
    public function getRuntimeLoaders(): array
    {
        $runtimeLoader = new class() implements RuntimeLoaderInterface {
            public function load($class): Runtime
            {
                Assert::string($class);
                Assert::same($class, Runtime::class);

                $consentContext = new class() implements ConsentContextInterface {
                    public function get(): Consent
                    {
                        return new Consent(new ClientId('client_id'), true, false, false);
                    }
                };

                return new Runtime($consentContext);
            }
        };

        return [$runtimeLoader];
    }

    public function getExtensions(): array
    {
        return [
            new Extension(),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/';
    }
}
