<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\Consent\ConsentCheckerInterface;
use Setono\SyliusConsentManagementPlugin\Checker\SessionCachedConsentChecker;
use Setono\SyliusConsentManagementPlugin\Event\ConsentUpdated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionCachedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $decoratedChecker;

    private ObjectProphecy $requestStack;

    private SessionCachedConsentChecker $checker;

    private string $sessionKey = 'test_session_key';

    protected function setUp(): void
    {
        $this->decoratedChecker = $this->prophesize(ConsentCheckerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->checker = new SessionCachedConsentChecker(
            $this->decoratedChecker->reveal(),
            $this->requestStack->reveal(),
            $this->sessionKey,
        );
    }

    /**
     * @test
     */
    public function it_delegates_to_decorated_checker_when_no_main_request(): void
    {
        $this->requestStack->getMainRequest()->willReturn(null);
        $this->decoratedChecker->isGranted('category')->willReturn(true);

        $result = $this->checker->isGranted('category');

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function it_delegates_to_decorated_checker_when_no_previous_session(): void
    {
        $request = $this->prophesize(Request::class);
        $request->hasPreviousSession()->willReturn(false);

        $this->requestStack->getMainRequest()->willReturn($request->reveal());
        $this->decoratedChecker->isGranted('category')->willReturn(false);

        $result = $this->checker->isGranted('category');

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function it_caches_consent_in_session(): void
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->has($this->sessionKey)->willReturn(false);
        $session->set($this->sessionKey, [])->shouldBeCalled();
        $session->get($this->sessionKey)->willReturn([]);
        $session->set($this->sessionKey, ['category' => true])->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $request->hasPreviousSession()->willReturn(true);
        $request->getSession()->willReturn($session->reveal());

        $this->requestStack->getMainRequest()->willReturn($request->reveal());
        $this->decoratedChecker->isGranted('category')->willReturn(true);

        $result = $this->checker->isGranted('category');

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function it_uses_cached_consent_from_session(): void
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->has($this->sessionKey)->willReturn(true);
        $session->get($this->sessionKey)->willReturn(['category' => true]);

        $request = $this->prophesize(Request::class);
        $request->hasPreviousSession()->willReturn(true);
        $request->getSession()->willReturn($session->reveal());

        $this->requestStack->getMainRequest()->willReturn($request->reveal());
        // This should not be called because the consent is already in the session
        $this->decoratedChecker->isGranted('category')->shouldNotBeCalled();

        $result = $this->checker->isGranted('category');

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function it_invalidates_session_cache(): void
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->remove($this->sessionKey)->willReturn(null)->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $request->hasPreviousSession()->willReturn(true);
        $request->getSession()->willReturn($session->reveal());

        $this->requestStack->getMainRequest()->willReturn($request->reveal());

        $this->checker->invalidate();
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_when_no_main_request(): void
    {
        $this->requestStack->getMainRequest()->willReturn(null)->shouldBeCalled();

        $this->checker->invalidate();

        // Assert that the method was called and returned early
        $this->requestStack->getMainRequest()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_when_no_previous_session(): void
    {
        $request = $this->prophesize(Request::class);
        $request->hasPreviousSession()->willReturn(false)->shouldBeCalled();
        // getSession should not be called
        $request->getSession()->shouldNotBeCalled();

        $this->requestStack->getMainRequest()->willReturn($request->reveal());

        $this->checker->invalidate();

        // Assert that the method was called and returned early
        $this->requestStack->getMainRequest()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_subscribes_to_consent_updated_event(): void
    {
        $events = SessionCachedConsentChecker::getSubscribedEvents();

        self::assertArrayHasKey(ConsentUpdated::class, $events);
        self::assertEquals('invalidate', $events[ConsentUpdated::class]);
    }
}
