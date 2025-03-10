<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\Consent\ConsentCheckerInterface;
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

        $this->checker = new RequestBasedConsentChecker($this->decoratedChecker->reveal(), $this->requestStack);
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
}
