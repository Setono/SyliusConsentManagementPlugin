<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class Cookie implements CookieInterface
{
    use TimestampableTrait;
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;

        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $name = null;

    protected ?string $exampleValue = null;

    protected ?string $url = null;

    protected ?ServiceInterface $service = null;

    protected bool $necessary = false;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function preUpdate(): void
    {
        // when a cookie is necessary we don't want to associate this cookie with any service
        if (true === $this->necessary) {
            $this->setService(null);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExampleValue(): ?string
    {
        return $this->exampleValue;
    }

    public function setExampleValue(string $exampleValue): void
    {
        $this->exampleValue = $exampleValue;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getService(): ?ServiceInterface
    {
        return $this->service;
    }

    public function setService(?ServiceInterface $service): void
    {
        $this->service = $service;
    }

    public function isNecessary(): bool
    {
        return $this->necessary;
    }

    public function setNecessary(bool $necessary): void
    {
        $this->necessary = $necessary;
    }

    public function getExpiry(): ?string
    {
        return $this->getTranslation()->getExpiry();
    }

    public function setExpiry(string $expiry): void
    {
        $this->getTranslation()->setExpiry($expiry);
    }

    public function getPurpose(): ?string
    {
        return $this->getTranslation()->getPurpose();
    }

    public function setPurpose(string $purpose): void
    {
        $this->getTranslation()->setPurpose($purpose);
    }

    /**
     * @return CookieTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var CookieTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): CookieTranslationInterface
    {
        return new CookieTranslation();
    }
}
