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
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentType;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepositoryInterface;
use Setono\SyliusConsentManagementPlugin\Widget\ConsentWidgetInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

/**
 * The coverage of this class is handled by the behat tests
 *
 * @codeCoverageIgnore
 */
final class ConsentAction
{
    private FormFactoryInterface $formFactory;

    private Environment $twig;

    private ConsentEntryRepositoryInterface $consentEntryRepository;

    private ServiceRepositoryInterface $serviceRepository;

    private ClientIdProviderInterface $clientIdProvider;

    private ConsentEntryFactoryInterface $consentEntryFactory;

    private EntityManagerInterface $consentEntryManager;

    private ConsentWidgetInterface $consentWidget;

    private ConsentWidgetCookieManagerInterface $consentWidgetCookieManager;

    private ConsentContextInterface $consentContext;

    private EventDispatcherInterface $eventDispatcher;

    private UrlGeneratorInterface $urlGenerator;

    private ChannelContextInterface $channelContext;

    public function __construct(
        FormFactoryInterface $formFactory,
        Environment $twig,
        ConsentEntryRepositoryInterface $consentEntryRepository,
        ServiceRepositoryInterface $serviceRepository,
        ClientIdProviderInterface $clientIdProvider,
        ConsentEntryFactoryInterface $consentEntryFactory,
        EntityManagerInterface $consentEntryManager,
        ConsentWidgetInterface $consentWidget,
        ConsentWidgetCookieManagerInterface $consentWidgetCookieManager,
        ConsentContextInterface $consentContext,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator,
        ChannelContextInterface $channelContext,
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
        $this->eventDispatcher = $eventDispatcher;
        $this->urlGenerator = $urlGenerator;
        $this->channelContext = $channelContext;
    }

    public function __invoke(Request $request): Response
    {
        $consent = $this->consentContext->getConsent();
        $decided = !$this->consentWidget->show();
        if ($decided) {
            $consentCommand = ConsentCommand::fromConsent($consent);
        } else {
            // If not decided - we want all options to be set to true
            $consentCommand = new ConsentCommand();
        }

        $form = $this->formFactory->create(ConsentType::class, $consentCommand);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clientId = $this->clientIdProvider->getClientId();
            $consentEntry = $this->consentEntryRepository->findOneFromClientId($clientId);
            if (null === $consentEntry) {
                /** @var ConsentEntryInterface $consentEntry */
                $consentEntry = $this->consentEntryFactory->createForClientId($clientId);

                $this->consentEntryManager->persist($consentEntry);
            }

            $consentEntry->populateFromRequest($request);
            $consentEntry->populateFromConsentCommand($consentCommand);

            $this->consentEntryManager->flush();

            $this->eventDispatcher->dispatch(new ConsentUpdated(
                $consentCommand->getConsent($clientId),
                $this->consentContext->getConsent(),
            ));

            if ($request->isXmlHttpRequest()) {
                $response = new Response('', Response::HTTP_NO_CONTENT);
            } else {
                $redirectUrl = $this->getRedirectUrl($request, 'setono_sylius_consent_management_shop_consent_update');
                $response = new RedirectResponse($redirectUrl);
            }

            $this->consentWidgetCookieManager->write($response);

            return $response;
        }

        if (!$request->isXmlHttpRequest()) {
            $template = $this->getTemplate($request, '@SetonoSyliusConsentManagementPlugin/shop/widget.html.twig');

            return new Response($this->twig->render($template, [
                'decided' => $decided,
                'consent' => json_encode($consent, JSON_THROW_ON_ERROR),
                'form' => $form->createView(),
                'services' => $this->serviceRepository->findEnabledIndexedByCategory($this->channelContext->getChannel()),
            ]), 200);
        }

        // we know the status code should be 400 if the the form was submitted
        // because if the form was valid another response would have been sent above
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    private function getTemplate(Request $request, string $defaultTemplate): string
    {
        $syliusParameters = [];

        if ($request->attributes->has('_sylius')) {
            /** @var array|mixed $syliusParameters */
            $syliusParameters = $request->attributes->get('_sylius');
            Assert::isArray($syliusParameters);
        }

        /** @var string|mixed $template */
        $template = $syliusParameters['template'] ?? $defaultTemplate;
        Assert::string($template);

        return $template;
    }

    private function getRedirectUrl(Request $request, string $defaultRoute): string
    {
        $syliusParameters = [];

        if ($request->attributes->has('_sylius')) {
            /** @var array|mixed $syliusParameters */
            $syliusParameters = $request->attributes->get('_sylius');
            Assert::isArray($syliusParameters);
        }

        /** @var string|mixed $route */
        $route = $syliusParameters['redirect']['route'] ?? $defaultRoute;
        Assert::string($route);

        /** @var array|mixed $parameters */
        $parameters = $syliusParameters['redirect']['parameters'] ?? [];
        Assert::isArray($parameters);

        return $this->urlGenerator->generate($route, $parameters);
    }
}
