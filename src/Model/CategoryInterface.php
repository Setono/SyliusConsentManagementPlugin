<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface CategoryInterface extends ResourceInterface, TranslatableInterface, CodeAwareInterface, \Stringable
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getDescription(): ?string;

    public function setDescription(string $description): void;

    public function isNecessary(): bool;

    public function setNecessary(bool $necessary): void;

    public function getPosition(): int;

    public function setPosition(?int $position): void;

    /**
     * @return Collection<array-key, ServiceInterface>
     */
    public function getServices(): Collection;

    /**
     * @return Collection<array-key, ServiceInterface>
     */
    public function getServicesWithAtLeastOneCookie(): Collection;

    public function addService(ServiceInterface $service): void;

    public function removeService(ServiceInterface $service): void;

    public function hasService(ServiceInterface $service): bool;
}
