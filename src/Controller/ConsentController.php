<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

final class ConsentController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly WidgetConfigProviderInterface $widgetConfigProvider,
    ) {
    }

    public function widget(): Response
    {
        $form = $this->createForm();

        return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
            'form' => $form->createView(),
            'widgetConfiguration' => $this->widgetConfigProvider->getWidgetConfig(),
        ]));
    }

    public function update(Request $request): Response
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            throw new BadRequestHttpException('Form is not submitted');
        }

        if (!$form->isValid()) {
            throw new BadRequestHttpException(sprintf('Form is not valid: %s', $form->getErrors(true)));
        }

        $consentEntry = $form->getData();
        dd($consentEntry);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function createForm(): FormInterface
    {
        // todo use factory to create entity
        return $this->formFactory->create(ConsentEntryType::class, new ConsentEntry(), [
            'csrf_protection' => false,
        ]);
    }
}
