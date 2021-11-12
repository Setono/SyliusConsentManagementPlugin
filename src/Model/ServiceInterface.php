<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface ServiceInterface extends ResourceInterface, TimestampableInterface, TranslatableInterface, CodeAwareInterface
{
    public const CATEGORY_PREFERENCES = 'preferences';

    public const CATEGORY_STATISTICS = 'statistics';

    public const CATEGORY_MARKETING = 'marketing';

    public function __toString(): string;

    public function getId(): ?int;

    public function getCategory(): ?string;

    public function setCategory(?string $category): void;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getDescription(): ?string;

    public function setDescription(string $description): void;

    /**
     * @return Collection|CookieInterface[]
     *
     * @psalm-return Collection<array-key, CookieInterface>
     */
    public function getCookies(): Collection;

    public function addCookie(CookieInterface $cookie): void;

    public function removeCookie(CookieInterface $cookie): void;

    public function hasCookie(CookieInterface $cookie): bool;
}
