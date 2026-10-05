<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Checker\CachedConsentChecker;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Checker\CachedConsentChecker
 */
final class CachedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_caches(): void
    {
        $consentChecker = $this->prophesize(ConsentCheckerInterface::class);
        $consentChecker->isGranted(DefaultConsents::CONSENT_FUNCTIONAL)->willReturn(true)->shouldBeCalledOnce();

        $cachedConsentContext = new CachedConsentChecker($consentChecker->reveal());

        self::assertSame(
            $cachedConsentContext->isGranted(DefaultConsents::CONSENT_FUNCTIONAL),
            $cachedConsentContext->isGranted(DefaultConsents::CONSENT_FUNCTIONAL),
        );
    }

    /**
     * @test
     */
    public function it_does_not_reuse_the_cache_after_a_reset(): void
    {
        // E.g. a worker runtime where the next request comes from another visitor
        $consentChecker = $this->prophesize(ConsentCheckerInterface::class);
        $consentChecker->isGranted(DefaultConsents::CONSENT_MARKETING)->willReturn(true, false);

        $cachedConsentChecker = new CachedConsentChecker($consentChecker->reveal());

        self::assertTrue($cachedConsentChecker->isGranted(DefaultConsents::CONSENT_MARKETING));

        $cachedConsentChecker->reset();

        self::assertFalse($cachedConsentChecker->isGranted(DefaultConsents::CONSENT_MARKETING));
    }
}
