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
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Runtime
 */
final class RuntimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_consent_tag(): void
    {
        $runtime = self::getRuntime();
        self::assertSame('<script>const sscmConsent = {"clientId":"client_id","marketingGranted":true,"preferencesGranted":true,"statisticsGranted":true}</script>', $runtime->consentTag());
    }

    /**
     * @test
     */
    public function it_returns_client_id(): void
    {
        $runtime = self::getRuntime();

        self::assertSame('client_id', $runtime->clientId());
    }

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

        self::assertSame('<script src="js/test.js" async></script>', $runtime->scriptTag('js/test.js', 'marketing'));
    }

    /**
     * @test
     */
    public function it_generates_text_script_tag(): void
    {
        $runtime = self::getRuntime(self::getConsentContext(false));

        self::assertSame('<script type="text/plain" data-consent="marketing" src="js/test.js" async></script>', $runtime->scriptTag('js/test.js', 'marketing'));
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

        self::assertSame(' type="text/plain" data-consent="marketing"', $runtime->scriptTagAttributes('marketing'));
    }

    private static function getRuntime(ConsentContextInterface $consentContext = null): Runtime
    {
        if (null === $consentContext) {
            $consentContext = self::getConsentContext();
        }

        $widgetConfigProvider = new class implements WidgetConfigProviderInterface
        {
            public function getWidgetConfig(ChannelInterface $channel, string $locale): WidgetConfigInterface
            {
                return new WidgetConfig();
            }
        };

        $channelContext = new class implements ChannelContextInterface
        {
            public function getChannel(): ChannelInterface
            {
                return new Channel();
            }
        };

        $localeContext = new class implements LocaleContextInterface
        {
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
            private bool $marketing;

            private bool $preferences;

            private bool $statistics;

            public function __construct(bool $marketing, bool $preferences, bool $statistics)
            {
                $this->marketing = $marketing;
                $this->preferences = $preferences;
                $this->statistics = $statistics;
            }

            public function getConsent(): Consent
            {
                return new Consent(new ClientId('client_id'), $this->marketing, $this->preferences, $this->statistics);
            }
        };
    }
}
