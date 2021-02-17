<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Context;

use Setono\SyliusCookieConsentPlugin\Model\Consent;
use Setono\SyliusCookieConsentPlugin\Repository\ConsentEntryRepositoryInterface;

final class ORMBasedConsentContext implements ConsentContextInterface
{
    private ConsentContextInterface $decorated;

    private ConsentEntryRepositoryInterface $consentEntryRepository;

    public function __construct(ConsentContextInterface $decorated, ConsentEntryRepositoryInterface $consentEntryRepository)
    {
        $this->decorated = $decorated;
        $this->consentEntryRepository = $consentEntryRepository;
    }

    public function get(): Consent
    {
        $currentConsent = $this->decorated->get();

        $consent = $this->consentEntryRepository->findConsentFromClientId($currentConsent->getClientId());
        if (null === $consent) {
            return $currentConsent;
        }

        return $consent;
    }
}
