<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\Consent\ConsentCheckerInterface;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Setono\SyliusConsentManagementPlugin\Checker\RequestBasedConsentChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestBasedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ConsentCheckerInterface> */
    private ObjectProphecy $decoratedChecker;

    private RequestStack $requestStack;

    private RequestBasedConsentChecker $checker;

    protected function setUp(): void
    {
        $this->decoratedChecker = $this->prophesize(ConsentCheckerInterface::class);

        $this->requestStack = new RequestStack();

        // The overrides are always honoured in debug mode. See the it_*_in_production tests for signed overrides
        $this->checker = new RequestBasedConsentChecker($this->decoratedChecker->reveal(), $this->requestStack, debug: true);
    }

    /**
     * @test
     */
    public function it_grants_all(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent=1'));
        self::assertTrue($this->checker->isGranted('marketing'));
        self::assertTrue($this->checker->isGranted('random'));
    }

    /**
     * @test
     */
    public function it_denies_all(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent=0'));
        self::assertFalse($this->checker->isGranted('marketing'));
        self::assertFalse($this->checker->isGranted('random'));
    }

    /**
     * @test
     */
    public function it_grants_specific_consent1(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent[statistical]=1'));
        self::assertTrue($this->checker->isGranted('statistical'));
    }

    /**
     * @test
     */
    public function it_grants_specific_consent2(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent[statistical]=1'));
        $this->decoratedChecker->isGranted('functional')->shouldBeCalled();
        $this->checker->isGranted('functional');
    }

    /**
     * @test
     */
    public function it_ignores_unsigned_grants_in_production(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent=1'));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_honours_denials_in_production(): void
    {
        $this->requestStack->push(Request::create('https://example.com/?_consent[marketing]=0'));
        $this->decoratedChecker->isGranted('marketing')->willReturn(true);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_honours_signed_grants_in_production(): void
    {
        $this->requestStack->push(Request::create((new ConsentOverrideSigner('secret'))->signUrl('https://example.com/', '1', new \DateTimeImmutable('+1 hour'))));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertTrue($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_ignores_grants_signed_with_another_secret_in_production(): void
    {
        $this->requestStack->push(Request::create((new ConsentOverrideSigner('another secret'))->signUrl('https://example.com/', '1', new \DateTimeImmutable('+1 hour'))));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_ignores_expired_signed_grants_in_production(): void
    {
        $this->requestStack->push(Request::create((new ConsentOverrideSigner('secret'))->signUrl('https://example.com/', '1', new \DateTimeImmutable('-1 second'))));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_ignores_signed_grants_with_an_added_category_in_production(): void
    {
        $url = (new ConsentOverrideSigner('secret'))->signUrl('https://example.com/', ['statistical' => '1'], new \DateTimeImmutable('+1 hour'));
        $this->requestStack->push(Request::create($url . '&_consent[marketing]=1'));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);
        $this->decoratedChecker->isGranted('statistical')->willReturn(false);

        $checker = $this->createProductionChecker();
        self::assertFalse($checker->isGranted('marketing'));
        self::assertFalse($checker->isGranted('statistical'));
    }

    /**
     * @test
     */
    public function it_ignores_signed_grants_with_an_extended_expiry_in_production(): void
    {
        $url = (new ConsentOverrideSigner('secret'))->signUrl('https://example.com/', '1', new \DateTimeImmutable('-1 second'));
        $this->requestStack->push(Request::create((string) preg_replace('/_consent_expires=\d+/', '_consent_expires=' . strtotime('+1 hour'), $url)));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    private function createProductionChecker(): RequestBasedConsentChecker
    {
        return new RequestBasedConsentChecker($this->decoratedChecker->reveal(), $this->requestStack, new ConsentOverrideSigner('secret'), false);
    }
}
