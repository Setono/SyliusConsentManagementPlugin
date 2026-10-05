<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetStyleRenderer;

final class WidgetStyleRendererTest extends TestCase
{
    use ProphecyTrait;

    private WidgetStyleRenderer $widgetStyleRenderer;

    /** @var ObjectProphecy<WidgetConfigProviderInterface> */
    private ObjectProphecy $widgetConfigProvider;

    protected function setUp(): void
    {
        $this->widgetConfigProvider = $this->prophesize(WidgetConfigProviderInterface::class);
        $this->widgetStyleRenderer = new WidgetStyleRenderer($this->widgetConfigProvider->reveal());
    }

    /**
     * @test
     */
    public function it_renders_empty_string_when_layout_is_empty(): void
    {
        $widgetConfig = $this->prophesize(WidgetConfigInterface::class);
        $widgetConfig->getLayout()->willReturn([]);

        $result = $this->widgetStyleRenderer->render($widgetConfig->reveal());

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function it_renders_correct_css_styles(): void
    {
        $widgetConfig = $this->prophesize(WidgetConfigInterface::class);
        $widgetConfig->getLayout()->willReturn([
            'backgroundColor' => '#fff',
            'fontSize' => '14px',
            'showBorder' => true,
        ]);

        $result = $this->widgetStyleRenderer->render($widgetConfig->reveal());

        self::assertSame('<style>.sscm-widget {--sscm-widget-background-color: #fff;--sscm-widget-font-size: 14px;--sscm-widget-show-border: true;}</style>', $result);
    }

    /**
     * @test
     */
    public function it_skips_values_and_keys_that_could_break_out_of_the_style_tag(): void
    {
        $widgetConfig = $this->prophesize(WidgetConfigInterface::class);
        $widgetConfig->getLayout()->willReturn([
            'backgroundColor' => '#fff;}</style><script>alert(1)</script>',
            'buttonPadding' => 'calc(100% - 20px) 10px',
            'borderRadius' => '4px; color: red',
            'maxWidth' => "url('https://evil.example')",
            'color}</style><script>' => 'red',
            'buttonAcceptAllColor' => 'rgba(0, 0, 0, 0.5)',
        ]);

        $result = $this->widgetStyleRenderer->render($widgetConfig->reveal());

        self::assertSame('<style>.sscm-widget {--sscm-widget-button-padding: calc(100% - 20px) 10px;--sscm-widget-button-accept-all-color: rgba(0, 0, 0, 0.5);}</style>', $result);
    }

    /**
     * @test
     */
    public function it_handles_null_widget_config_and_uses_provider(): void
    {
        $widgetConfig = $this->prophesize(WidgetConfigInterface::class);
        $widgetConfig->getLayout()->willReturn([
            'color' => 'red',
        ]);

        // Mock provider to return a WidgetConfig
        $this->widgetConfigProvider->getWidgetConfig()->willReturn($widgetConfig->reveal());

        $result = $this->widgetStyleRenderer->render();

        self::assertSame('<style>.sscm-widget {--sscm-widget-color: red;}</style>', $result);
    }
}
