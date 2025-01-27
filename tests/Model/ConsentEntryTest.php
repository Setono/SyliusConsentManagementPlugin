<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Model\FormerConsent;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\ConsentEntry
 */
final class ConsentEntryTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_and_gets(): void
    {
        $clientId = new ClientId('client_id');

        $consentEntry = new ConsentEntry();
        $consentEntry->setClientId($clientId);
        $consentEntry->setUserAgent('user agent');
        $consentEntry->setUrl('https://example.com');
        $consentEntry->setIp('192.168.1.1');

        self::assertNull($consentEntry->getId());
        self::assertSame($clientId, $consentEntry->getClientId());
        self::assertSame('user agent', $consentEntry->getUserAgent());
        self::assertSame('https://example.com', $consentEntry->getUrl());
        self::assertSame('192.168.1.1', $consentEntry->getIp());
    }

    /**
     * @test
     */
    public function it_populates_from_former_consent(): void
    {
        $formerConsent = new FormerConsent(true, true, true);
        $formerConsent->url = 'https://example.com';
        $formerConsent->userAgent = 'Chr0me';
        $formerConsent->ip = '127.0.0.1';

        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromFormerConsent($formerConsent);

        self::assertSame('https://example.com', $consentEntry->getUrl());
        self::assertSame('Chr0me', $consentEntry->getUserAgent());
        self::assertSame('127.0.0.1', $consentEntry->getIp());
    }
}
