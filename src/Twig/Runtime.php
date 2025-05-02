<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function Symfony\Component\String\u;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly WidgetConfigProviderInterface $widgetConfigProvider,
        private readonly WidgetDisplayDeciderInterface $widgetDisplayDecider,
        private readonly WidgetRendererInterface $widgetRenderer,
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
        $layout = $this->filterLayout($this->widgetConfig()->getLayout());

        if ([] === $layout) {
            return '';
        }

        $ret = [];

        foreach ($layout as $directive => $value) {
            $ret[] = sprintf('--sscm-widget-%s: %s;', u($directive)->snake()->replace('_', '-'), self::castScalar($value));
        }

        return sprintf("<style>\n.sscm-widget {\n%s\n}\n</style>", implode("\n", $ret));
    }

    /**
     * @return array<string, scalar>
     */
    private function filterLayout(array $layout): array
    {
        $ret = [];

        foreach ($layout as $directive => $value) {
            if (!is_string($directive) || !is_scalar($value)) {
                continue;
            }

            if ('' === $value) {
                continue;
            }

            $ret[$directive] = $value;
        }

        return $ret;
    }

    /**
     * @param scalar $value
     */
    private static function castScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
