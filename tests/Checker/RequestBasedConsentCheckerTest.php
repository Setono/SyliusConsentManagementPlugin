<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
use Setono\SyliusConsentManagementPlugin\Checker\RequestBasedConsentChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestBasedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    private ConsentCheckerInterface $decoratedChecker;

    private RequestStack $requestStack;
    private RequestBasedConsentChecker $checker;

    protected function setUp(): void
    {
        $this->decoratedChecker = $this->prophesize(ConsentCheckerInterface::class);

        $this->checker = new RequestBasedConsentChecker($this->decoratedChecker->reveal());
    }

    /**
     * @test
     */
    public function it_grants_all(): void
    {
        $context = new RequestBasedConsentChecker(
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
        $context = new RequestBasedConsentChecker(
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
        $context = new RequestBasedConsentChecker(
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
