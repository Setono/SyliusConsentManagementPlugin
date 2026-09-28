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
        $query = (new ConsentOverrideSigner('secret'))->sign('1', new \DateTimeImmutable('+1 hour'));
        $this->requestStack->push(Request::create('https://example.com/?' . http_build_query($query)));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertTrue($this->createProductionChecker()->isGranted('marketing'));
    }

    /**
     * @test
     */
    public function it_ignores_grants_signed_with_another_secret_in_production(): void
    {
        $query = (new ConsentOverrideSigner('another secret'))->sign('1', new \DateTimeImmutable('+1 hour'));
        $this->requestStack->push(Request::create('https://example.com/?' . http_build_query($query)));
        $this->decoratedChecker->isGranted('marketing')->willReturn(false);

        self::assertFalse($this->createProductionChecker()->isGranted('marketing'));
    }

    private function createProductionChecker(): RequestBasedConsentChecker
    {
        return new RequestBasedConsentChecker($this->decoratedChecker->reveal(), $this->requestStack, new ConsentOverrideSigner('secret'), false);
    }
}
