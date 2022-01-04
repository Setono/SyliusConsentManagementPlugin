<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface CookieTranslationInterface extends ResourceInterface, TranslationInterface
{
    public function getExpiry(): ?string;

    public function setExpiry(string $expiry): void;

    public function getPurpose(): ?string;

    public function setPurpose(string $purpose): void;
}
