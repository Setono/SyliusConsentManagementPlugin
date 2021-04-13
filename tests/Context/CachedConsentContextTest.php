<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Context\CachedConsentContext;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Context\CachedConsentContext
 */
final class CachedConsentContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_caches(): void
    {
        $decorated = new class() implements ConsentContextInterface {
            public function getConsent(): Consent
            {
                return new Consent(new ClientId('client_id'), true, true, true);
            }
        };

        $cachedConsentContext = new CachedConsentContext($decorated);
        $res1 = $cachedConsentContext->getConsent();
        $res2 = $cachedConsentContext->getConsent();

        self::assertSame($res1, $res2);
    }
}
