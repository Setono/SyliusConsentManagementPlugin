<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

use Doctrine\ORM\EntityManagerInterface;
use const JSON_THROW_ON_ERROR;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Cookie\ConsentWidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Webmozart\Assert\Assert;

/**
 * The coverage of this class is handled by the behat tests
 *
 * @codeCoverageIgnore
 */
final class ConsentWidgetAction
{
    private FormFactoryInterface $formFactory;

    private Environment $twig;

    private ConsentEntryRepositoryInterface $consentEntryRepository;

    private ServiceRepositoryInterface $serviceRepository;

    private ClientIdProviderInterface $clientIdProvider;

    private FactoryInterface $consentEntryFactory;

    private EntityManagerInterface $consentEntryManager;

    private ConsentWidgetInterface $consentWidget;

    private ConsentWidgetCookieManagerInterface $consentWidgetCookieManager;

    private ConsentContextInterface $consentContext;

    public function __construct(
        FormFactoryInterface $formFactory,
        Environment $twig,
        ConsentEntryRepositoryInterface $consentEntryRepository,
        ServiceRepositoryInterface $serviceRepository,
        ClientIdProviderInterface $clientIdProvider,
        FactoryInterface $consentEntryFactory,
        EntityManagerInterface $consentEntryManager,
        ConsentWidgetInterface $consentWidget,
        ConsentWidgetCookieManagerInterface $consentWidgetCookieManager,
        ConsentContextInterface $consentContext
    ) {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
        $this->consentEntryRepository = $consentEntryRepository;
        $this->serviceRepository = $serviceRepository;
        $this->clientIdProvider = $clientIdProvider;
        $this->consentEntryFactory = $consentEntryFactory;
        $this->consentEntryManager = $consentEntryManager;
        $this->consentWidget = $consentWidget;
        $this->consentWidgetCookieManager = $consentWidgetCookieManager;
        $this->consentContext = $consentContext;
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            if (!$this->consentWidget->show()) {
                return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
                    'decided' => true,
                    'consent' => json_encode($this->consentContext->getConsent(), JSON_THROW_ON_ERROR),
                ]), 200);
            }

            $form = $this->formFactory->create(ConsentType::class, new ConsentCommand());

            return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
                'decided' => false,
                'consent' => json_encode($this->consentContext->getConsent(), JSON_THROW_ON_ERROR),
                'form' => $form->createView(),
                'services' => $this->serviceRepository->findAllIndexedByCategory(),
            ]), 200);
        }

        $form = $this->formFactory->create(ConsentType::class, new ConsentCommand());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clientId = $this->clientIdProvider->getClientId();
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
            $this->consentWidgetCookieManager->write($response);

            return $response;
        }

        return new Response('', 400); // we know the status code should be 400 if the the form was submitted because if the form was valid another response would have been sent above
    }
}
