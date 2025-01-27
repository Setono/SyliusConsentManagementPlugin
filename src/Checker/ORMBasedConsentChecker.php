<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\Consent\ConsentCheckerInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;

final class ORMBasedConsentChecker implements ConsentCheckerInterface
{
    public function __construct(
        private readonly ConsentCheckerInterface $decorated,
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
        private readonly ClientContextInterface $clientContext,
    ) {
    }

    public function isGranted(string $consent): bool
    {
        $consentEntry = $this->consentEntryRepository->findOneFromClient($this->clientContext->getClient());
        if (null === $consentEntry) {
            return $this->decorated->isGranted($consent);
        }

        return in_array($consent, $consentEntry->getConsentedCategories(), true);
    }
}
