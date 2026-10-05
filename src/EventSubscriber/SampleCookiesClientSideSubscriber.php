<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class SampleCookiesClientSideSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SampleDeciderInterface $sampleDecider,
        private readonly SampleTokenManagerInterface $sampleTokenManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'sample',
        ];
    }

    public function sample(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Some of this code is taken from \Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener::onKernelResponse
        if ($request->isXmlHttpRequest() ||
            $response->isRedirection() ||
            'html' !== $request->getRequestFormat() ||
            false !== stripos($response->headers->get('Content-Disposition', ''), 'attachment;') ||
            ($response->headers->has('Content-Type') && !str_contains($response->headers->get('Content-Type') ?? '', 'html')) ||
            !$this->sampleDecider->sample($request, SampleDeciderInterface::CONTEXT_CLIENT_SIDE)
        ) {
            return;
        }

        $content = $response->getContent();
        if (false === $content) {
            return;
        }

        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $sample = "\n" . $this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig', [
                'token' => $this->sampleTokenManager->create(),
            ]) . "\n";
            $content = substr($content, 0, $pos) . $sample . substr($content, $pos);
            $response->setContent($content);

            // The token can only be used once and expires, so no cache may serve this page to anybody else or again
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-store');
        }
    }
}
