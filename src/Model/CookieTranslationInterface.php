<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface CookieTranslationInterface extends ResourceInterface, TranslationInterface
{
    public function getDescription(): ?string;

    public function setDescription(?string $description): void;
}
