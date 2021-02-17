<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Controller\Action;

use Setono\SyliusCookieConsentPlugin\Form\Type\ConsentType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ConsentBlockAction
{
    /**
     * @var FormFactoryInterface
     */
    private FormFactoryInterface $formFactory;
    /**
     * @var Environment
     */
    private Environment $twig;

    public function __construct(FormFactoryInterface $formFactory, Environment $twig)
    {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(ConsentType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {

        }

        return new Response($this->twig->render('@SetonoSyliusCookieConsentPlugin/shop/consent_block.html.twig', [
            'form' => $form->createView()
        ]));
    }
}
