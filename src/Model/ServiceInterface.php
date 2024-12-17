<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface ServiceInterface extends ResourceInterface, TimestampableInterface, TranslatableInterface, CodeAwareInterface, \Stringable
{
    public function getId(): ?int;

    public function getCategory(): ?CategoryInterface;

    public function setCategory(?CategoryInterface $category): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    /**
     * @return Collection<array-key, CookieInterface>
     */
    public function getCookies(): Collection;

    public function addCookie(CookieInterface $cookie): void;

    public function removeCookie(CookieInterface $cookie): void;

    public function hasCookie(CookieInterface $cookie): bool;
}
