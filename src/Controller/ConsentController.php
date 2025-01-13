<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class ConsentController
{
    use ORMTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly ConsentEntryFactoryInterface $consentEntryFactory,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function widget(Environment $twig, WidgetConfigProviderInterface $widgetConfigProvider): Response
    {
        $form = $this->createForm();

        return new Response($twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
            'form' => $form->createView(),
            'widgetConfiguration' => $widgetConfigProvider->getWidgetConfig(),
        ]));
    }

    public function update(Request $request): JsonResponse
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new BadRequestHttpException(sprintf('Form is not valid: %s', $form->getErrors(true)));
        }

        /** @var mixed $consentEntry */
        $consentEntry = $form->getData();
        Assert::object($consentEntry);

        $this->getManager($consentEntry)->persist($consentEntry);
        $this->getManager($consentEntry)->flush();

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    private function createForm(): FormInterface
    {
        return $this->formFactory->create(ConsentEntryType::class, $this->consentEntryFactory->createNew(), [
            'csrf_protection' => false,
        ]);
    }
}
