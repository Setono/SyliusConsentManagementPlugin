<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManager;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\SampleCookiesClientSideSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

final class SampleCookiesClientSideSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_injects_the_sampling_script_with_a_valid_token(): void
    {
        $tokenManager = new SampleTokenManager('secret');

        $sampleDecider = $this->prophesize(SampleDeciderInterface::class);
        $sampleDecider->sample(Argument::type(Request::class), SampleDeciderInterface::CONTEXT_CLIENT_SIDE)->willReturn(true);

        $twig = $this->prophesize(Environment::class);
        $twig->render('@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig', Argument::that(
            static fn (array $context): bool => is_string($context['token'] ?? null) && $tokenManager->isValid($context['token']),
        ))->willReturn('<script>/* sample */</script>')->shouldBeCalledOnce();

        $response = new Response('<html><body><h1>Shop</h1></body></html>', headers: ['Content-Type' => 'text/html']);
        $event = new ResponseEvent($this->prophesize(HttpKernelInterface::class)->reveal(), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response);

        (new SampleCookiesClientSideSubscriber($twig->reveal(), $sampleDecider->reveal(), $tokenManager))->sample($event);

        self::assertStringContainsString("<script>/* sample */</script>\n</body>", (string) $response->getContent());
    }
}
