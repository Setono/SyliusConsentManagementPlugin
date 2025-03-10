<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use League\Uri\Contracts\UriInterface;
use League\Uri\Uri;
use Sylius\Component\Channel\Model\ChannelInterface;

final class Url implements \Stringable
{
    public UriInterface $value;

    public function __construct(
        UriInterface|string $value,
        public ?ChannelInterface $channel = null,
        public ?string $localeCode = null,
    ) {
        if (is_string($value)) {
            $value = Uri::createFromString($value);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
