<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use PHPUnit\Framework\TestCase;
use Setono\Consent\ConsentCheckerInterface;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
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

        self::assertTrue($runtime->functionalGranted());
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

        self::assertTrue($runtime->statisticalGranted());
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
        $runtime = self::getRuntime(self::getConsentChecker(false));

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
        $runtime = self::getRuntime(self::getConsentChecker(false));

        self::assertSame(' type="text/plain" data-sscm-consent="marketing"', $runtime->scriptTagAttributes('marketing'));
    }

    /**
     * @test
     */
    public function it_returns_widget_config(): void
    {
        $runtime = self::getRuntime(self::getConsentChecker());
        self::assertInstanceOf(WidgetConfigInterface::class, $runtime->widgetConfig());
    }

    private static function getRuntime(ConsentCheckerInterface $consentChecker = null): Runtime
    {
        if (null === $consentChecker) {
            $consentChecker = self::getConsentChecker();
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

        return new Runtime($consentChecker, $widgetConfigProvider, $channelContext, $localeContext);
    }

    private static function getConsentChecker(bool $marketing = true, bool $preferences = true, bool $statistics = true): StaticConsentChecker
    {
        return new StaticConsentChecker([
            'marketing' => $marketing,
            'preferences' => $preferences,
            'statistics' => $statistics,
        ]);
    }
}
