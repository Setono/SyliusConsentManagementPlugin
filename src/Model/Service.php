<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class Service implements ServiceInterface
{
    use TimestampableTrait;
    use TranslatableTrait {
        __construct as private __translatableConstruct;

        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?CategoryInterface $category = null;

    /** @var Collection<array-key, CookieInterface> */
    protected Collection $cookies;

    public function __construct()
    {
        $this->cookies = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCategory(): ?CategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?CategoryInterface $category): void
    {
        $this->category = $category;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getTranslation()->setName($name);
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): void
    {
        $this->getTranslation()->setDescription($description);
    }

    public function getPrivacyPolicyUrl(): ?string
    {
        return $this->getTranslation()->getPrivacyPolicyUrl();
    }

    public function setPrivacyPolicyUrl(?string $privacyPolicyUrl): void
    {
        $this->getTranslation()->setPrivacyPolicyUrl($privacyPolicyUrl);
    }

    /**
     * @return ServiceTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var ServiceTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    public function getCookies(): Collection
    {
        return $this->cookies;
    }

    public function addCookie(CookieInterface $cookie): void
    {
        if (!$this->hasCookie($cookie)) {
            $cookie->setService($this);
            $this->cookies->add($cookie);
        }
    }

    public function removeCookie(CookieInterface $cookie): void
    {
        if ($this->hasCookie($cookie)) {
            $cookie->setService(null);
            $this->cookies->removeElement($cookie);
        }
    }

    public function hasCookie(CookieInterface $cookie): bool
    {
        return $this->cookies->contains($cookie);
    }

    protected function createTranslation(): ServiceTranslationInterface
    {
        return new ServiceTranslation();
    }
}
