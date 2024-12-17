<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

use Doctrine\ORM\EntityManagerInterface;
use const JSON_THROW_ON_ERROR;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\Consent\Event\ConsentUpdated;
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
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly Environment $twig,
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ClientIdProviderInterface $clientIdProvider,
        private readonly FactoryInterface $consentEntryFactory,
        private readonly EntityManagerInterface $consentEntryManager,
        private readonly ConsentWidgetInterface $consentWidget,
        private readonly ConsentWidgetCookieManagerInterface $consentWidgetCookieManager,
        private readonly ConsentContextInterface $consentContext,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(ConsentType::class, new ConsentCommand(), [
            'csrf_protection' => false,
        ]);

        if (!$request->isXmlHttpRequest()) {
            if (!$this->consentWidget->show()) {
                return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
                    'decided' => true,
                    'consent' => json_encode($this->consentContext->getConsent(), JSON_THROW_ON_ERROR),
                ]), 200);
            }

            return new Response($this->twig->render('@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig', [
                'decided' => false,
                'consent' => json_encode($this->consentContext->getConsent(), JSON_THROW_ON_ERROR),
                'form' => $form->createView(),
                'services' => $this->serviceRepository->findAllIndexedByCategory(),
            ]), 200);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clientId = $this->clientIdProvider->getClientId();
            $consentEntry = $this->consentEntryRepository->findOneFromClientId($clientId);
            if (null === $consentEntry) {
                /** @var ConsentEntryInterface $consentEntry */
                $consentEntry = $this->consentEntryFactory->createNew();

                $this->consentEntryManager->persist($consentEntry);
            }

            /** @var ConsentCommand|mixed $consentCommand */
            $consentCommand = $form->getData();
            Assert::isInstanceOf($consentCommand, ConsentCommand::class);

            $consentEntry->setClientId($clientId);
            $consentEntry->populateFromRequest($request);
            $consentEntry->populateFromConsentCommand($consentCommand);

            $this->consentEntryManager->flush();

            $this->eventDispatcher->dispatch(new ConsentUpdated(
                $consentCommand->getConsent($clientId),
                $this->consentContext->getConsent(),
            ));

            $response = new Response('', 204);
            $this->consentWidgetCookieManager->write($response);

            return $response;
        }

        return new Response('', 400); // we know the status code should be 400 if the the form was submitted because if the form was valid another response would have been sent above
    }
}
