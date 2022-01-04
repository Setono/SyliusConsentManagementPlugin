<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\AbstractTranslation;

class CookieTranslation extends AbstractTranslation implements CookieTranslationInterface
{
    protected ?int $id = null;

    protected ?string $expiry = null;

    protected ?string $purpose = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpiry(): ?string
    {
        return $this->expiry;
    }

    public function setExpiry(string $expiry): void
    {
        $this->expiry = $expiry;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): void
    {
        $this->purpose = $purpose;
    }
}
