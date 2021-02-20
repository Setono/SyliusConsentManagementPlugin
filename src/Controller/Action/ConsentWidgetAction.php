<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusConsentManagementPlugin\ClientId\ClientIdInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
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

    private string $cookieName;

    public function __construct(
        FormFactoryInterface $formFactory,
        Environment $twig,
        ConsentEntryRepositoryInterface $consentEntryRepository,
        ClientIdInterface $clientId,
        FactoryInterface $consentEntryFactory,
        EntityManagerInterface $consentEntryManager,
        string $cookieName
    ) {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
        $this->consentEntryRepository = $consentEntryRepository;
        $this->clientId = $clientId;
        $this->consentEntryFactory = $consentEntryFactory;
        $this->consentEntryManager = $consentEntryManager;
        $this->cookieName = $cookieName;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->cookies->has($this->cookieName)) {
            return new Response();
        }

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

            $response = new Response('', 204);
            $response->headers->setCookie(Cookie::create($this->cookieName, '1', new \DateTime('+360 days')));

            return $response;
        }

        return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/consent.html.twig', [
            'form' => $form->createView(),
        ]), $form->isSubmitted() ? 400 : 200); // we know the status code should be 400 if the the form was submitted because if the form was valid another response would have been sent above
    }
}
