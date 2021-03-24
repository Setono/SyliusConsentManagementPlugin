<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\SyliusConsentManagementPlugin\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Context\DefaultConsentContext;
use Setono\SyliusConsentManagementPlugin\Context\RequestBasedConsentContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestBasedConsentContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_grants_all(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(), self::getRequestStack('_consent=1'), self::getClientIdProvider()
        );

        $consent = $context->get();

        self::assertTrue($consent->isMarketingGranted());
        self::assertTrue($consent->isPreferencesGranted());
        self::assertTrue($consent->isStatisticsGranted());
    }

    /**
     * @test
     */
    public function it_denies_all(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(), self::getRequestStack('_consent=0'), self::getClientIdProvider()
        );

        $consent = $context->get();

        self::assertFalse($consent->isMarketingGranted());
        self::assertFalse($consent->isPreferencesGranted());
        self::assertFalse($consent->isStatisticsGranted());
    }

    /**
     * @test
     */
    public function it_grants_marketing(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(), self::getRequestStack('_consent[marketing]=1'), self::getClientIdProvider()
        );

        $consent = $context->get();

        self::assertTrue($consent->isMarketingGranted());
        self::assertFalse($consent->isPreferencesGranted());
        self::assertFalse($consent->isStatisticsGranted());
    }

    /**
     * @test
     */
    public function it_grants_preferences(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(), self::getRequestStack('_consent[preferences]=1'), self::getClientIdProvider()
        );

        $consent = $context->get();

        self::assertFalse($consent->isMarketingGranted());
        self::assertTrue($consent->isPreferencesGranted());
        self::assertFalse($consent->isStatisticsGranted());
    }

    /**
     * @test
     */
    public function it_grants_statistics(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentContext(), self::getRequestStack('_consent[statistics]=1'), self::getClientIdProvider()
        );

        $consent = $context->get();

        self::assertFalse($consent->isMarketingGranted());
        self::assertFalse($consent->isPreferencesGranted());
        self::assertTrue($consent->isStatisticsGranted());
    }

    private static function getConsentContext(): ConsentContextInterface
    {
        $clientIdProvider = new class() implements ClientIdProviderInterface {
            public function get(): ClientId
            {
                return new ClientId('client_id');
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
            public function get(): ClientId
            {
                return new ClientId('client_id');
            }
        };
    }
}
