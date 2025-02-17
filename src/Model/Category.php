<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class Category implements CategoryInterface
{
    use TimestampableTrait;
    use TranslatableTrait {
        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected bool $necessary = false;

    protected int $position = 0;

    /** @var Collection<array-key, ServiceInterface> */
    protected Collection $services;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->services = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) ($this->getName() ?? $this->getCode());
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

    public function isNecessary(): bool
    {
        return $this->necessary;
    }

    public function setNecessary(bool $necessary): void
    {
        $this->necessary = $necessary;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = (int) $position;
    }

    public function getServices(): Collection
    {
        return $this->services;
    }

    public function getServicesWithAtLeastOneCookie(): Collection
    {
        /** @var ArrayCollection<array-key, ServiceInterface> $services */
        $services = new ArrayCollection();

        foreach ($this->services as $service) {
            if ($service->getCookies()->count() > 0) {
                $services->add($service);
            }
        }

        return $services;
    }

    public function addService(ServiceInterface $service): void
    {
        if (!$this->hasService($service)) {
            $service->setCategory($this);
            $this->services->add($service);
        }
    }

    public function removeService(ServiceInterface $service): void
    {
        if ($this->hasService($service)) {
            $service->setCategory(null);
            $this->services->removeElement($service);
        }
    }

    public function hasService(ServiceInterface $service): bool
    {
        return $this->services->contains($service);
    }

    /**
     * @return CategoryTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var CategoryTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): CategoryTranslationInterface
    {
        return new CategoryTranslation();
    }
}
