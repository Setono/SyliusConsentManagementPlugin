<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Resource\Factory\FactoryInterface;

interface CookieFactoryInterface extends FactoryInterface
{
    public function createNew(): CookieInterface;

    public function createWithName(string $name): CookieInterface;
}
