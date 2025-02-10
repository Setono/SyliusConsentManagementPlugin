<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\AbstractTranslation;

class CookieTranslation extends AbstractTranslation implements CookieTranslationInterface
{
    protected ?int $id = null;

    protected ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
