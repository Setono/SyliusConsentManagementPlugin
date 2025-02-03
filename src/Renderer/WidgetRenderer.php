<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Renderer;

use Setono\SyliusConsentManagementPlugin\Form\Factory\ConsentEntryTypeFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Twig\Environment;

final class WidgetRenderer implements WidgetRendererInterface
{
    public function __construct(
        private readonly ConsentEntryTypeFactoryInterface $consentEntryTypeFactory,
        private readonly WidgetConfigProviderInterface $widgetConfigProvider,
        private readonly Environment $twig,
    ) {
    }

    public function render(): string
    {
        $form = $this->consentEntryTypeFactory->createNew();

        return $this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
            'form' => $form->createView(),
            'widgetConfiguration' => $this->widgetConfigProvider->getWidgetConfig(),
        ]);
    }
}
