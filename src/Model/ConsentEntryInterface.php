<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface ConsentEntryInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;
}
