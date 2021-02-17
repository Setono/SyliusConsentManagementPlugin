<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Controller\Action;

use Setono\SyliusCookieConsentPlugin\Form\Type\UpdateConsentType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ConsentBlockAction
{
    private FormFactoryInterface $formFactory;

    private Environment $twig;

    public function __construct(FormFactoryInterface $formFactory, Environment $twig)
    {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(UpdateConsentType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
        }

        return new Response($this->twig->render('@SetonoSyliusCookieConsentPlugin/shop/consent_block.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}
