<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Controller\Action;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCookieConsentPlugin\ClientId\ClientIdInterface;
use Setono\SyliusCookieConsentPlugin\Form\Type\ConsentType;
use Setono\SyliusCookieConsentPlugin\Model\ConsentEntryInterface;
use Setono\SyliusCookieConsentPlugin\Repository\ConsentEntryRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class ConsentWidgetAction
{
    private FormFactoryInterface $formFactory;

    private Environment $twig;

    private ConsentEntryRepositoryInterface $consentEntryRepository;

    private ClientIdInterface $clientId;

    private FactoryInterface $consentEntryFactory;

    private EntityManagerInterface $consentEntryManager;

    public function __construct(
        FormFactoryInterface $formFactory,
        Environment $twig,
        ConsentEntryRepositoryInterface $consentEntryRepository,
        ClientIdInterface $clientId,
        FactoryInterface $consentEntryFactory,
        EntityManagerInterface $consentEntryManager
    ) {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
        $this->consentEntryRepository = $consentEntryRepository;
        $this->clientId = $clientId;
        $this->consentEntryFactory = $consentEntryFactory;
        $this->consentEntryManager = $consentEntryManager;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(ConsentType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clientId = $this->clientId->get();
            $consentEntry = $this->consentEntryRepository->findOneFromClientId($clientId);
            if (null === $consentEntry) {
                /** @var ConsentEntryInterface $consentEntry */
                $consentEntry = $this->consentEntryFactory->createNew();

                $this->consentEntryManager->persist($consentEntry);
            }

            $consentCommand = $form->getData();
            Assert::isInstanceOf($consentCommand, ConsentCommand::class);

            $consentEntry->setClientId($clientId);
            $consentEntry->populateFromRequest($request);
            $consentEntry->populateFromConsentCommand($consentCommand);

            $this->consentEntryManager->flush();

            return new Response('', 204);
        }

        return new Response($this->twig->render('@SetonoSyliusCookieConsentPlugin/shop/consent.html.twig', [
            'form' => $form->createView(),
        ]), $form->isSubmitted() ? 400 : 200); // we know the status code should be 400 if the the form was submitted because if the form was valid another response would have been sent above
    }
}
