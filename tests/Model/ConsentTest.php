<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\Consent;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\Consent
 */
final class ConsentTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_and_gets(): void
    {
        $clientId = new ClientId('client_id');
        $consent = self::getConsent($clientId);

        self::assertSame($clientId, $consent->getClientId());
        self::assertTrue($consent->isPreferencesGranted());
        self::assertTrue($consent->isMarketingGranted());
        self::assertTrue($consent->isStatisticsGranted());
    }

    /**
     * @test
     */
    public function it_serializes(): void
    {
        $consent = self::getConsent();

        $str = json_encode($consent);

        self::assertSame('{"clientId":"client_id","preferences":true,"statistics":true,"marketing":true}', $str);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_wrong_permission_is_asked(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::getConsent()->isConsentGranted('non_existing_permission');
    }

    /**
     * @test
     */
    public function it_returns_correct_consent_based_on_string(): void
    {
        self::assertTrue(self::getConsent()->isConsentGranted('marketing'));
    }

    private static function getConsent(ClientId $clientId = null): Consent
    {
        $clientId = $clientId ?? new ClientId('client_id');

        return new Consent($clientId, true, true, true);
    }
}
