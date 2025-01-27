<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Runtime
 */
final class RuntimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_preferences_consent_status(): void
    {
        $runtime = self::getRuntime();

        self::assertTrue($runtime->preferencesGranted());
    }

    /**
     * @test
     */
    public function it_returns_marketing_consent_status(): void
    {
        $runtime = self::getRuntime();

        self::assertTrue($runtime->marketingGranted());
    }

    /**
     * @test
     */
    public function it_returns_statistics_consent_status(): void
    {
        $runtime = self::getRuntime();

        self::assertTrue($runtime->statisticsGranted());
    }

    /**
     * @test
     */
    public function it_generates_executable_script_tag(): void
    {
        $runtime = self::getRuntime();

        self::assertSame('<script src="js/test.js"></script>', $runtime->scriptTag('js/test.js', 'marketing'));
    }

    /**
     * @test
     */
    public function it_generates_text_script_tag(): void
    {
        $runtime = self::getRuntime(self::getConsentContext(false));

        self::assertSame('<script type="text/plain" data-sscm-consent="marketing" data-sscm-src="js/test.js"></script>', $runtime->scriptTag('js/test.js', 'marketing'));
    }

    /**
     * @test
     */
    public function it_generates_executable_script_tag_attributes(): void
    {
        $runtime = self::getRuntime();

        self::assertSame('', $runtime->scriptTagAttributes('marketing'));
    }

    /**
     * @test
     */
    public function it_generates_text_script_tag_attributes(): void
    {
        $runtime = self::getRuntime(self::getConsentContext(false));

        self::assertSame(' type="text/plain" data-sscm-consent="marketing"', $runtime->scriptTagAttributes('marketing'));
    }

    /**
     * @test
     */
    public function it_returns_widget_config(): void
    {
        $runtime = self::getRuntime(self::getConsentContext());
        self::assertInstanceOf(WidgetConfigInterface::class, $runtime->widgetConfig());
    }

    private static function getRuntime(ConsentContextInterface $consentContext = null): Runtime
    {
        if (null === $consentContext) {
            $consentContext = self::getConsentContext();
        }

        $widgetConfigProvider = new class() implements WidgetConfigProviderInterface {
            public function getWidgetConfig(ChannelInterface $channel = null, string $locale = null): WidgetConfigInterface
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

    private static function getConsentContext(bool $marketing = true, bool $preferences = true, bool $statistics = true): ConsentContextInterface
    {
        return new class($marketing, $preferences, $statistics) implements ConsentContextInterface {
            public function __construct(private readonly bool $marketing, private readonly bool $preferences, private readonly bool $statistics)
            {
            }

            public function getConsent(): Consent
            {
                return new Consent(new ClientId('client_id'), $this->marketing, $this->preferences, $this->statistics);
            }
        };
    }
}
