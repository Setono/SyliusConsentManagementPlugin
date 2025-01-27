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
}
