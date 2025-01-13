<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Controller\ConsentCommand;
use Sylius\Component\Resource\Model\TimestampableTrait;

class ConsentEntry implements ConsentEntryInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?ClientId $clientId = null;

    protected ?string $ip = null;

    protected ?string $url = null;

    protected ?string $userAgent = null;

    protected bool $marketingGranted = false;

    protected bool $preferencesGranted = false;

    protected bool $statisticsGranted = false;

    /** @var list<string> */
    protected array $consentedCategories = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?ClientId
    {
        return $this->clientId;
    }

    public function setClientId(ClientId $clientId): void
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

    /**
     * todo: Remove
     *
     * @deprecated
     */
    public function isMarketingGranted(): bool
    {
        return $this->marketingGranted;
    }

    /**
     * todo: Remove
     *
     * @deprecated
     */
    public function setMarketingGranted(bool $marketingGranted): void
    {
        $this->marketingGranted = $marketingGranted;
    }

    /**
     * todo: Remove
     *
     * @deprecated
     */
    public function isPreferencesGranted(): bool
    {
        return $this->preferencesGranted;
    }

    /**
     * todo: Remove
     *
     * @deprecated
     */
    public function setPreferencesGranted(bool $preferencesGranted): void
    {
        $this->preferencesGranted = $preferencesGranted;
    }

    public function getConsentedCategories(): array
    {
        return $this->consentedCategories;
    }

    public function setConsentedCategories(array $consentedCategories): void
    {
        $this->consentedCategories = [];

        foreach ($consentedCategories as $consentedCategory) {
            $this->addConsentedCategory($consentedCategory);
        }
    }

    public function addConsentedCategory(CategoryInterface|string $consentedCategory): void
    {
        if ($consentedCategory instanceof CategoryInterface) {
            $consentedCategory = (string) $consentedCategory->getCode();
        }

        $this->consentedCategories[] = $consentedCategory;
    }

    public function populateFromConsentCommand(ConsentCommand $consentCommand): void
    {
        $this->marketingGranted = $consentCommand->marketingGranted;
        $this->preferencesGranted = $consentCommand->preferencesGranted;
        $this->statisticsGranted = $consentCommand->statisticsGranted;
    }

    public function populateFromFormerConsent(FormerConsent $formerConsent): void
    {
        $this->marketingGranted = $formerConsent->marketingGranted;
        $this->preferencesGranted = $formerConsent->preferencesGranted;
        $this->statisticsGranted = $formerConsent->statisticsGranted;

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
