<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\SessionCachedWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionCachedWidgetDisplayDeciderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WidgetDisplayDeciderInterface> */
    private ObjectProphecy $decorated;

    /** @var ObjectProphecy<Request> */
    private ObjectProphecy $request;

    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $session;

    private SessionCachedWidgetDisplayDecider $decider;

    protected function setUp(): void
    {
        $this->decorated = $this->prophesize(WidgetDisplayDeciderInterface::class);

        $this->session = $this->prophesize(SessionInterface::class);

        $this->request = $this->prophesize(Request::class);
        $this->request->getSession()->willReturn($this->session->reveal());

        $this->decider = new SessionCachedWidgetDisplayDecider($this->decorated->reveal());
    }

    /**
     * @test
     */
    public function it_calls_decorated_when_session_is_not_started(): void
    {
        $this->request->hasPreviousSession()->willReturn(false);
        $this->decorated->display($this->request->reveal())->willReturn(true)->shouldBeCalled();

        self::assertTrue($this->decider->display($this->request->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_false_if_session_key_is_set(): void
    {
        $this->request->hasPreviousSession()->willReturn(true);

        $this->session->has('sscm_widget')->willReturn(true);

        $this->decorated->display(Argument::any())->shouldNotBeCalled();

        self::assertFalse($this->decider->display($this->request->reveal()));
    }

    /**
     * @test
     */
    public function it_sets_session_and_returns_false(): void
    {
        $this->request->hasPreviousSession()->willReturn(true);

        $this->session->has('sscm_widget')->willReturn(false);
        $this->session->set('sscm_widget', 1)->shouldBeCalled();

        $this->decorated->display($this->request->reveal())->willReturn(false)->shouldBeCalled();

        self::assertFalse($this->decider->display($this->request->reveal()));
    }
}
