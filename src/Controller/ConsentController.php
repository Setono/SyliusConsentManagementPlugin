<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Form\Factory\ConsentEntryTypeFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Webmozart\Assert\Assert;

final class ConsentController
{
    use ORMTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function widget(WidgetRendererInterface $widgetRenderer): Response
    {
        return new Response($widgetRenderer->render());
    }

    public function update(
        Request $request,
        WidgetCookieManagerInterface $consentWidgetCookieManager,
        ConsentEntryTypeFactoryInterface $consentEntryTypeFactory,
    ): JsonResponse {
        $form = $consentEntryTypeFactory->createNew($request);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new BadRequestHttpException(sprintf('Form is not valid: %s', $form->getErrors(true)));
        }

        /** @var mixed|ConsentEntryInterface $consentEntry */
        $consentEntry = $form->getData();
        Assert::isInstanceOf($consentEntry, ConsentEntryInterface::class);

        $this->getManager($consentEntry)->persist($consentEntry);
        $this->getManager($consentEntry)->flush();

        $response = new JsonResponse($consentEntry->getConsentedCategories());
        $consentWidgetCookieManager->write($response);

        return $response;
    }
}
