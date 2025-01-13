<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Twig\Extension;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
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
            /**
             * @param string $class
             */
            public function load($class): Runtime
            {
                Assert::same($class, Runtime::class);

                $consentContext = new class() implements ConsentContextInterface {
                    public function getConsent(): Consent
                    {
                        return new Consent(new ClientId('client_id'), true, false, false);
                    }
                };

                $widgetConfigProvider = new class() implements WidgetConfigProviderInterface {
                    public function getWidgetConfig(ChannelInterface $channel, string $locale): WidgetConfigInterface
                    {
                        return new WidgetConfig();
                    }
                };

                $channelContext = new class() implements ChannelContextInterface {
                    public function getChannel(): ChannelInterface
                    {
                        return new Channel();
                    }
                };

                $localeContext = new class() implements LocaleContextInterface {
                    public function getLocaleCode(): string
                    {
                        return 'en_US';
                    }
                };

                return new Runtime($consentContext, $widgetConfigProvider, $channelContext, $localeContext);
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
