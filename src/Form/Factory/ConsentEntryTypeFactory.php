<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Factory;

use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class ConsentEntryTypeFactory implements ConsentEntryTypeFactoryInterface
{
    public function __construct(
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
        private readonly ClientContextInterface $clientContext,
        private readonly ConsentEntryFactoryInterface $consentEntryFactory,
        private readonly FormFactoryInterface $formFactory,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function createNew(Request $request = null): FormInterface
    {
        $request = $request ?? $this->requestStack->getMainRequest();
        Assert::notNull($request);

        $consentEntry = $this->consentEntryRepository->findOneFromClient($this->clientContext->getClient());

        return $this->formFactory->create(ConsentEntryType::class, $consentEntry ?? $this->consentEntryFactory->createFromRequest($request), [
            'csrf_protection' => false,
        ]);
    }
}
