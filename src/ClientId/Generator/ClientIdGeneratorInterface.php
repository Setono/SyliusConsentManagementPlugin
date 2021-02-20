<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\ClientId\Generator;

interface ClientIdGeneratorInterface
{
    /**
     * Generates a unique client id
     */
    public function generate(): string;
}
