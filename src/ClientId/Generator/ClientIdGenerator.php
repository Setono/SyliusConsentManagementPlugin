<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\ClientId\Generator;

use Symfony\Component\Uid\Uuid;

final class ClientIdGenerator implements ClientIdGeneratorInterface
{
    public function generate(): string
    {
        return (string) Uuid::v4();
    }
}
