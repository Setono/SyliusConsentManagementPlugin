<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\WidgetConfigProviderInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
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
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
        private readonly ClientContextInterface $clientContext,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function widget(Request $request, Environment $twig, WidgetConfigProviderInterface $widgetConfigProvider): Response
    {
        $form = $this->createForm($request);

        return new Response($twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
            'form' => $form->createView(),
            'widgetConfiguration' => $widgetConfigProvider->getWidgetConfig(),
        ]));
    }

    public function update(Request $request): JsonResponse
    {
        $form = $this->createForm($request);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new BadRequestHttpException(sprintf('Form is not valid: %s', $form->getErrors(true)));
        }

        /** @var mixed|ConsentEntryInterface $consentEntry */
        $consentEntry = $form->getData();
        Assert::isInstanceOf($consentEntry, ConsentEntryInterface::class);

        $this->getManager($consentEntry)->persist($consentEntry);
        $this->getManager($consentEntry)->flush();

        return new JsonResponse($consentEntry->getConsentedCategories());
    }

    private function createForm(Request $request): FormInterface
    {
        $consentEntry = $this->consentEntryRepository->findOneFromClient($this->clientContext->getClient());

        return $this->formFactory->create(ConsentEntryType::class, $consentEntry ?? $this->consentEntryFactory->createFromRequest($request), [
            'csrf_protection' => false,
        ]);
    }
}
