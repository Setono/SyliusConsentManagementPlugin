<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetStyleRendererInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

final class WidgetRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly WidgetConfigProviderInterface $widgetConfigProvider,
        private readonly WidgetDisplayDeciderInterface $widgetDisplayDecider,
        private readonly WidgetRendererInterface $widgetRenderer,
        private readonly WidgetStyleRendererInterface $widgetStyleRenderer,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function shouldDisplayWidget(Request $request = null): bool
    {
        $request = $request ?? $this->requestStack->getMainRequest();
        if (null === $request) {
            return false;
        }

        return $this->widgetDisplayDecider->display($request);
    }

    public function widget(Request $request = null): string
    {
        $request = $request ?? $this->requestStack->getMainRequest();
        if (null === $request) {
            throw new \RuntimeException('The consent widget cannot be rendered in a non request/response lifecycle');
        }

        if (!$this->widgetDisplayDecider->display($request)) {
            return '';
        }

        return $this->widgetRenderer->render();
    }

    public function widgetConfig(ChannelInterface $channel = null, string $locale = null): WidgetConfigInterface
    {
        return $this->widgetConfigProvider->getWidgetConfig($channel, $locale);
    }

    public function widgetLayoutStyleTag(): string
    {
        return $this->widgetStyleRenderer->render();
    }
}
