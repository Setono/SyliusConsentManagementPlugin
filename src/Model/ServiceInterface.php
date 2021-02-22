<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface ServiceInterface extends ResourceInterface, TimestampableInterface, TranslatableInterface
{
    public function getId(): ?int;

    public function getCategory(): ?string;

    public function setCategory(string $category): void;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getDescription(): ?string;

    public function setDescription(string $description): void;
}
