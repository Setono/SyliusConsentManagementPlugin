<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Twig;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\ConsentBundle\Checker\StaticConsentChecker;
use Setono\SyliusConsentManagementPlugin\Decider\WidgetDisplayDeciderInterface;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfig;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Twig\Extension;
use Setono\SyliusConsentManagementPlugin\Twig\Runtime;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
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

        $consentChecker = new StaticConsentChecker([
            DefaultConsents::CONSENT_FUNCTIONAL => true,
            DefaultConsents::CONSENT_MARKETING => false,
            DefaultConsents::CONSENT_STATISTICAL => true,
        ]);

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn(new Channel());

        $localeContext = $this->prophesize(LocaleContextInterface::class);
        $localeContext->getLocaleCode()->willReturn('en_US');

        $category1 = new Category();
        $category1->setCode(DefaultConsents::CONSENT_FUNCTIONAL);

        $category2 = new Category();
        $category2->setCode(DefaultConsents::CONSENT_STATISTICAL);

        $categoryRepository = $this->prophesize(RepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([$category1, $category2]);

        $widgetDisplayDecider = $this->prophesize(WidgetDisplayDeciderInterface::class);

        $requestStack = new RequestStack();

        $runtimeLoader = new class($consentChecker, $widgetConfigProvider->reveal(), $channelContext->reveal(), $localeContext->reveal(), $categoryRepository->reveal(), $widgetDisplayDecider->reveal(), $requestStack) implements RuntimeLoaderInterface {
            public function __construct(
                private readonly ConsentCheckerInterface $consentChecker,
                private readonly WidgetConfigProviderInterface $widgetConfigProvider,
                private readonly ChannelContextInterface $channelContext,
                private readonly LocaleContextInterface $localeContext,
                private readonly RepositoryInterface $categoryRepository,
                private readonly WidgetDisplayDeciderInterface $widgetDisplayDecider,
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
                    $this->consentChecker,
                    $this->widgetConfigProvider,
                    $this->channelContext,
                    $this->localeContext,
                    $this->categoryRepository,
                    $this->widgetDisplayDecider,
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
