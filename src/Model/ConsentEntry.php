<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;

class ConsentEntry implements ConsentEntryInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $clientId = null;

    protected ?string $ip = null;

    protected bool $preferences = false;

    protected bool $statistics = false;

    protected bool $marketing = false;

    public function getId(): ?int
    {
        return $this->id;
    }
}
