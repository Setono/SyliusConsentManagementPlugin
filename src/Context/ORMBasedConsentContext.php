<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;

final class ORMBasedConsentContext implements ConsentContextInterface
{
    public function __construct(
        private readonly ConsentContextInterface $decorated,
        private readonly ConsentEntryRepositoryInterface $consentEntryRepository,
    ) {
    }

    public function getConsent(): Consent
    {
        $currentConsent = $this->decorated->getConsent();

        $consent = $this->consentEntryRepository->findConsentFromClientId($currentConsent->getClientId());
        if (null === $consent) {
            return $currentConsent;
        }

        return $consent;
    }
}
