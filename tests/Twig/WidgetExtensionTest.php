<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManager;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\CompositeWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\CookieBasedWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplay\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetStyleRendererInterface;
use Setono\SyliusConsentManagementPlugin\Twig\WidgetExtension;
use Setono\SyliusConsentManagementPlugin\Twig\WidgetRuntime;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Test\IntegrationTestCase;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\WidgetExtension
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\WidgetRuntime
 */
final class WidgetExtensionTest extends IntegrationTestCase
{
    use ProphecyTrait;

    public function getRuntimeLoaders(): array
    {
        $widgetConfigProvider = $this->prophesize(WidgetConfigProviderInterface::class);
        $widgetConfigProvider->getWidgetConfig(Argument::cetera())->willReturn(new WidgetConfig());

        $category1 = new Category();
        $category1->setCode(DefaultConsents::CONSENT_FUNCTIONAL);

        $category2 = new Category();
        $category2->setCode(DefaultConsents::CONSENT_STATISTICAL);

        $widgetDisplayDecider = new CompositeWidgetDisplayDecider();
        $widgetDisplayDecider->add(new CookieBasedWidgetDisplayDecider(new WidgetCookieManager('sscm_widget', '1')));

        $widgetRenderer = $this->prophesize(WidgetRendererInterface::class);
        $widgetStyleRenderer = $this->prophesize(WidgetStyleRendererInterface::class);

        $requestStack = new RequestStack();

        $runtimeLoader = new class($widgetConfigProvider->reveal(), $widgetDisplayDecider, $widgetRenderer->reveal(), $widgetStyleRenderer->reveal(), $requestStack) implements RuntimeLoaderInterface {
            public function __construct(
                private readonly WidgetConfigProviderInterface $widgetConfigProvider,
                private readonly WidgetDisplayDeciderInterface $widgetDisplayDecider,
                private readonly WidgetRendererInterface $widgetRenderer,
                private readonly WidgetStyleRendererInterface $widgetStyleRenderer,
                private readonly RequestStack $requestStack,
            ) {
            }

            /**
             * @param string $class
             */
            public function load($class): WidgetRuntime
            {
                Assert::same($class, WidgetRuntime::class);

                return new WidgetRuntime(
                    $this->widgetConfigProvider,
                    $this->widgetDisplayDecider,
                    $this->widgetRenderer,
                    $this->widgetStyleRenderer,
                    $this->requestStack,
                );
            }
        };

        return [$runtimeLoader];
    }

    public function getExtensions(): array
    {
        return [
            new WidgetExtension(),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/WidgetFixtures/';
    }
}
