<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;

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
        $consentEntry = new ConsentEntry();
        $consentEntry->setClientId('client_id');
        $consentEntry->setUserAgent('user agent');
        $consentEntry->setUrl('https://example.com');
        $consentEntry->setIp('192.168.1.1');

        self::assertNull($consentEntry->getId());
        self::assertSame('client_id', $consentEntry->getClientId());
        self::assertSame('user agent', $consentEntry->getUserAgent());
        self::assertSame('https://example.com', $consentEntry->getUrl());
        self::assertSame('192.168.1.1', $consentEntry->getIp());
    }
}
