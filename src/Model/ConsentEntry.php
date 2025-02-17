<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\Consent\DefaultConsents;
use Sylius\Component\Resource\Model\TimestampableTrait;

class ConsentEntry implements ConsentEntryInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $clientId = null;

    protected ?string $ip = null;

    protected ?string $url = null;

    protected ?string $userAgent = null;

    /** @var list<string>|null */
    protected ?array $consentedCategories = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getConsentedCategories(): array
    {
        return $this->consentedCategories ?? [];
    }

    public function setConsentedCategories(?array $consentedCategories): void
    {
        if ([] === $consentedCategories || null === $consentedCategories) {
            $this->consentedCategories = null;

            return;
        }

        foreach ($consentedCategories as $consentedCategory) {
            $this->addConsentedCategory($consentedCategory);
        }
    }

    public function addConsentedCategory(CategoryInterface|string $consentedCategory): void
    {
        if (null === $this->consentedCategories) {
            $this->consentedCategories = [];
        }

        if ($consentedCategory instanceof CategoryInterface) {
            $consentedCategory = (string) $consentedCategory->getCode();
        }

        if (in_array($consentedCategory, $this->consentedCategories, true)) {
            return;
        }

        $this->consentedCategories[] = $consentedCategory;
    }

    public function populateFromFormerConsent(FormerConsent $formerConsent): void
    {
        if ($formerConsent->marketingGranted) {
            $this->addConsentedCategory(DefaultConsents::CONSENT_MARKETING);
        }

        if ($formerConsent->preferencesGranted) {
            $this->addConsentedCategory(DefaultConsents::CONSENT_FUNCTIONAL);
        }

        if ($formerConsent->statisticsGranted) {
            $this->addConsentedCategory(DefaultConsents::CONSENT_STATISTICAL);
        }

        if (null !== $formerConsent->url) {
            $this->url = $formerConsent->url;
        }

        if (null !== $formerConsent->userAgent) {
            $this->userAgent = $formerConsent->userAgent;
        }

        if (null !== $formerConsent->ip) {
            $this->ip = $formerConsent->ip;
        }
    }
}
