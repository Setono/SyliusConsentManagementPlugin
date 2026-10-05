<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\CachedWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Symfony\Component\HttpFoundation\Request;

final class CachedWidgetDisplayDeciderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_caches_the_decision_per_request(): void
    {
        $firstVisitor = Request::create('/');
        $secondVisitor = Request::create('/', cookies: ['sscm_widget' => '1']);

        $decorated = $this->prophesize(WidgetDisplayDeciderInterface::class);
        $decorated->display($firstVisitor)->willReturn(true)->shouldBeCalledOnce();
        $decorated->display($secondVisitor)->willReturn(false)->shouldBeCalledOnce();

        $decider = new CachedWidgetDisplayDecider($decorated->reveal());

        self::assertTrue($decider->display($firstVisitor));
        self::assertTrue($decider->display($firstVisitor));
        self::assertFalse($decider->display($secondVisitor));
        self::assertFalse($decider->display($secondVisitor));
    }

    /**
     * @test
     */
    public function it_does_not_reuse_the_cache_after_a_reset(): void
    {
        $request = Request::create('/');

        $decorated = $this->prophesize(WidgetDisplayDeciderInterface::class);
        $decorated->display($request)->willReturn(true, false);

        $decider = new CachedWidgetDisplayDecider($decorated->reveal());

        self::assertTrue($decider->display($request));

        $decider->reset();

        self::assertFalse($decider->display($request));
    }
}
