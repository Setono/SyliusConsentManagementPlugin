<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\CachedConsentChecker;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Checker\CachedConsentChecker
 */
final class CachedConsentCheckerTest extends TestCase
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

        $cachedConsentContext = new CachedConsentChecker($decorated);
        $res1 = $cachedConsentContext->getConsent();
        $res2 = $cachedConsentContext->getConsent();

        self::assertSame($res1, $res2);
    }
}
