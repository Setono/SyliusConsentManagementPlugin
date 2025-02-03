<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface CookieInterface extends ResourceInterface, TimestampableInterface
{
    public const STATE_PENDING = 'pending';

    public const STATE_CONFIRMED = 'confirmed';

    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): void;

    /**
     * This is the URL where this cookie was seen the first time
     */
    public function getUrl(): ?string;

    public function setUrl(string $url): void;

    public function getState(): ?string;

    public function setState(?string $state): void;

    /**
     * Indicates how many times the cookie has occurred in a sample
     */
    public function getSamples(): int;

    public function setSamples(int $samples): void;

    public function increaseSamples(): void;

    public function getService(): ?ServiceInterface;

    public function setService(?ServiceInterface $service): void;
}
