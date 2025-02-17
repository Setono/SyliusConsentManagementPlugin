<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManager;
use Setono\SyliusConsentManagementPlugin\Decider\CompositeWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\CookieBasedWidgetDisplayDecider;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Twig\Extension;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Test\IntegrationTestCase;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Extension
 * @covers \Setono\SyliusConsentManagementPlugin\Twig\Runtime
 */
final class ExtensionTest extends IntegrationTestCase
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

        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([$category1, $category2]);

        $widgetDisplayDecider = new CompositeWidgetDisplayDecider();
        $widgetDisplayDecider->add(new CookieBasedWidgetDisplayDecider(new WidgetCookieManager('sscm_widget')));

        $widgetRenderer = $this->prophesize(WidgetRendererInterface::class);

        $requestStack = new RequestStack();

        $runtimeLoader = new class($widgetConfigProvider->reveal(), $categoryRepository->reveal(), $widgetDisplayDecider, $widgetRenderer->reveal(), $requestStack) implements RuntimeLoaderInterface {
            public function __construct(
                private readonly WidgetConfigProviderInterface $widgetConfigProvider,
                private readonly CategoryRepositoryInterface $categoryRepository,
                private readonly WidgetDisplayDeciderInterface $widgetDisplayDecider,
                private readonly WidgetRendererInterface $widgetRenderer,
                private readonly RequestStack $requestStack,
            ) {
            }

            /**
             * @param string $class
             */
            public function load($class): Runtime
            {
                Assert::same($class, Runtime::class);

                return new Runtime(
                    $this->widgetConfigProvider,
                    $this->categoryRepository,
                    $this->widgetDisplayDecider,
                    $this->widgetRenderer,
                    $this->requestStack,
                );
            }
        };

        return [$runtimeLoader];
    }

    public function getExtensions(): array
    {
        return [
            new Extension(),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/';
    }
}
