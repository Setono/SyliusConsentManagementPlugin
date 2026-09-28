<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Event\ConsentUpdated;
use Setono\SyliusConsentManagementPlugin\Form\Factory\ConsentEntryTypeFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Renderer\WidgetRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        EventDispatcherInterface $eventDispatcher,
    ): JsonResponse {
        if (!self::isSameOriginRequest($request)) {
            throw new AccessDeniedHttpException('The consent can only be updated from the store itself');
        }

        $form = $consentEntryTypeFactory->createNew($request);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new BadRequestHttpException(sprintf('Form is not valid: %s', $form->getErrors(true)));
        }

        /** @var mixed|ConsentEntryInterface $consentEntry */
        $consentEntry = $form->getData();
        Assert::isInstanceOf($consentEntry, ConsentEntryInterface::class);

        $consentEntry = $this->save($consentEntry);

        $response = new JsonResponse($consentEntry->getConsentedCategories());
        $consentWidgetCookieManager->write($response);

        $eventDispatcher->dispatch(new ConsentUpdated($consentEntry->getConsentedCategories()));

        return $response;
    }

    /**
     * Browsers send the Sec-Fetch-Site header (and the Origin header) when a page on another site submits a form to
     * this endpoint, while the widget's own requests are same-origin. Requests without either header can't be forged
     * by another site in a modern browser
     */
    private static function isSameOriginRequest(Request $request): bool
    {
        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if (null !== $fetchSite) {
            return 'same-origin' === $fetchSite;
        }

        $origin = $request->headers->get('Origin');
        if (null !== $origin) {
            // Only the host is compared, because the scheme and port can be misreported behind a proxy
            return parse_url($origin, \PHP_URL_HOST) === $request->getHost();
        }

        return true;
    }

    private function save(ConsentEntryInterface $consentEntry): ConsentEntryInterface
    {
        $manager = $this->getManager($consentEntry);

        try {
            $manager->persist($consentEntry);
            $manager->flush();

            return $consentEntry;
        } catch (UniqueConstraintViolationException $e) {
            // Another request for the same client (e.g. a double click or a second tab) created the entry in the meantime.
            // The failed flush closed the entity manager, so it is reset before the existing entry is updated
            foreach ($this->managerRegistry->getManagers() as $name => $registeredManager) {
                if ($registeredManager === $manager) {
                    $this->managerRegistry->resetManager($name);
                }
            }

            $existingEntry = $manager->getRepository($consentEntry::class)->findOneBy(['clientId' => $consentEntry->getClientId()]);
            if (!$existingEntry instanceof ConsentEntryInterface) {
                throw $e;
            }

            $existingEntry->setConsentedCategories($consentEntry->getConsentedCategories());
            $manager->flush();

            return $existingEntry;
        }
    }
}
