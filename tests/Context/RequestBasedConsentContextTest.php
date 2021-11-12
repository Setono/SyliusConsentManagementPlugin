<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\Consent\Context\DefaultConsentContext;
use Setono\SyliusConsentManagementPlugin\Context\RequestBasedConsentContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestBasedConsentContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_decorated_if_master_request_is_null(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            new RequestStack(),
            self::getClientIdProvider()
        );

        self::assertSame('decorated_client_id', $context->getConsent()->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_returns_decorated_if_request_uri_does_not_contain_consent(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('other_param=123'),
            self::getClientIdProvider()
        );

        self::assertSame('decorated_client_id', $context->getConsent()->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_grants_all(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('_consent=1'),
            self::getClientIdProvider()
        );

        $consent = $context->getConsent();

        self::assertTrue($consent->isMarketingConsentGranted());
        self::assertTrue($consent->isPreferencesConsentGranted());
        self::assertTrue($consent->isStatisticsConsentGranted());
    }

    /**
     * @test
     */
    public function it_denies_all(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('_consent=0'),
            self::getClientIdProvider()
        );

        $consent = $context->getConsent();

        self::assertFalse($consent->isMarketingConsentGranted());
        self::assertFalse($consent->isPreferencesConsentGranted());
        self::assertFalse($consent->isStatisticsConsentGranted());
    }

    /**
     * @test
     */
    public function it_grants_marketing(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('_consent[marketing]=1'),
            self::getClientIdProvider()
        );

        $consent = $context->getConsent();

        self::assertTrue($consent->isMarketingConsentGranted());
        self::assertFalse($consent->isPreferencesConsentGranted());
        self::assertFalse($consent->isStatisticsConsentGranted());
    }

    /**
     * @test
     */
    public function it_grants_preferences(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('_consent[preferences]=1'),
            self::getClientIdProvider()
        );

        $consent = $context->getConsent();

        self::assertFalse($consent->isMarketingConsentGranted());
        self::assertTrue($consent->isPreferencesConsentGranted());
        self::assertFalse($consent->isStatisticsConsentGranted());
    }

    /**
     * @test
     */
    public function it_grants_statistics(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(),
            self::getRequestStack('_consent[statistics]=1'),
            self::getClientIdProvider()
        );

        $consent = $context->getConsent();

        self::assertFalse($consent->isMarketingConsentGranted());
        self::assertFalse($consent->isPreferencesConsentGranted());
        self::assertTrue($consent->isStatisticsConsentGranted());
    }

    private static function getConsentContext(): ConsentContextInterface
    {
        $clientIdProvider = new class() implements ClientIdProviderInterface {
            public function getClientId(): ClientId
            {
                return new ClientId('decorated_client_id');
            }
        };

        return new DefaultConsentContext($clientIdProvider);
    }

    private static function getRequestStack(string $q): RequestStack
    {
        $request = Request::create('https://example.com/?' . $q);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    private static function getClientIdProvider(): ClientIdProviderInterface
    {
        return new class() implements ClientIdProviderInterface {
            public function getClientId(): ClientId
            {
                return new ClientId('client_id');
            }
        };
    }
}
