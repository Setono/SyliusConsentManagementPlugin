<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\ClientId;

interface ClientIdInterface
{
    /**
     * Returns the client id
     */
    public function get(): string;
}
