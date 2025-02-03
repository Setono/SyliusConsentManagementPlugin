<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;

class Cookie implements CookieInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $name = null;

    protected ?string $url = null;

    protected ?string $state = self::STATE_PENDING;

    protected int $samples = 0;

    protected ?ServiceInterface $service = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getSamples(): int
    {
        return $this->samples;
    }

    public function setSamples(int $samples): void
    {
        $this->samples = $samples;
    }

    public function increaseSamples(): void
    {
        ++$this->samples;
    }

    public function getService(): ?ServiceInterface
    {
        return $this->service;
    }

    public function setService(?ServiceInterface $service): void
    {
        $this->service = $service;
    }
}
