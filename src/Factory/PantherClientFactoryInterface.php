<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Symfony\Component\Panther\Client;

interface PantherClientFactoryInterface
{
    public function create(): Client;
}
