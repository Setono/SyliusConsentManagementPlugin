<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;

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
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);
        self::assertSame('<script>const sscmConsent = {"clientId":"client_id","marketingGranted":true,"preferencesGranted":true,"statisticsGranted":true}</script>', $runtime->consentTag());
    }

    /**
     * @test
     */
    public function it_returns_client_id(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertSame('client_id', $runtime->clientId());
    }

    /**
     * @test
     */
    public function it_returns_preferences_consent_status(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertTrue($runtime->preferencesGranted());
    }

    /**
     * @test
     */
    public function it_returns_marketing_consent_status(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertTrue($runtime->marketingGranted());
    }

    /**
     * @test
     */
    public function it_returns_statistics_consent_status(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertTrue($runtime->statisticsGranted());
    }

    /**
     * @test
     */
    public function it_generates_executable_script_tag(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertSame('<script src="js/test.js" async></script>', $runtime->scriptTag('js/test.js', 'marketing'));
    }

    /**
     * @test
     */
    public function it_generates_text_script_tag(): void
    {
        $consentContext = self::getConsentContext(false);
        $runtime = new Runtime($consentContext);

        self::assertSame('<script type="text/plain" data-consent="marketing" src="js/test.js" async></script>', $runtime->scriptTag('js/test.js', 'marketing'));
    }

    /**
     * @test
     */
    public function it_generates_executable_script_tag_attributes(): void
    {
        $consentContext = self::getConsentContext();
        $runtime = new Runtime($consentContext);

        self::assertSame('', $runtime->scriptTagAttributes('marketing'));
    }

    /**
     * @test
     */
    public function it_generates_text_script_tag_attributes(): void
    {
        $consentContext = self::getConsentContext(false);
        $runtime = new Runtime($consentContext);

        self::assertSame(' type="text/plain" data-consent="marketing"', $runtime->scriptTagAttributes('marketing'));
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
