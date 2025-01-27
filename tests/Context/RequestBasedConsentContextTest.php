<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
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
            self::getConsentChecker(),
            self::getRequestStack('_consent=1'),
        );

        self::assertTrue($context->isGranted('marketing'));
        self::assertTrue($context->isGranted('random'));
    }

    /**
     * @test
     */
    public function it_denies_all(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentChecker(),
            self::getRequestStack('_consent=0'),
        );

        self::assertFalse($context->isGranted('marketing'));
        self::assertFalse($context->isGranted('random'));
    }

    /**
     * @test
     */
    public function it_grants_specific_consent(): void
    {
        $context = new RequestBasedConsentContext(
            self::getConsentChecker(),
            self::getRequestStack('_consent[statistical]=1'),
        );

        self::assertFalse($context->isGranted('functional'));
        self::assertTrue($context->isGranted('statistical'));
    }

    private static function getConsentChecker(): ConsentCheckerInterface
    {
        return new StaticConsentChecker([
            DefaultConsents::CONSENT_FUNCTIONAL => true,
            DefaultConsents::CONSENT_MARKETING => true,
            DefaultConsents::CONSENT_STATISTICAL => true,
        ]);
    }

    private static function getRequestStack(string $q): RequestStack
    {
        $request = Request::create('https://example.com/?' . $q);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
