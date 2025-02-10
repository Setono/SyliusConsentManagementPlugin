<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class SampleCookiesClientSideSubscriber implements EventSubscriberInterface
{
    private readonly float $sampleRate;

    public function __construct(
        private readonly Environment $twig,
        private readonly FirewallMapInterface $firewallMap,
        /** @var array<array-key, string> $firewalls */
        private readonly array $firewalls,
        float $sampleRate,
    ) {
        Assert::lessThanEq($sampleRate, 1);

        $this->sampleRate = $sampleRate;
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
            !$this->collectSample($request)
        ) {
            return;
        }

        $content = $response->getContent();
        if (false === $content) {
            return;
        }

        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $sample = "\n" . $this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig') . "\n";
            $content = substr($content, 0, $pos) . $sample . substr($content, $pos);
            $response->setContent($content);
        }
    }

    private function collectSample(Request $request): bool
    {
        $sampleRateResult = random_int(1, mt_getrandmax()) / mt_getrandmax() <= $this->sampleRate;
        if (!$sampleRateResult) {
            return false;
        }

        $userAgent = $request->headers->get('User-Agent');
        if (!is_string($userAgent) || '' === $userAgent || !str_contains($userAgent, 'Chrome')) {
            return false;
        }

        if (!$this->firewallMap instanceof FirewallMap) {
            return true;
        }

        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        if (null === $firewallConfig) {
            return true;
        }

        return in_array($firewallConfig->getName(), $this->firewalls, true);
    }
}
