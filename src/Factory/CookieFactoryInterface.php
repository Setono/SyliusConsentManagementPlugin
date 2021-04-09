<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface CookieFactoryInterface extends FactoryInterface
{
    public function createNew(): CookieInterface;

    public function createWithData(string $name, string $url): CookieInterface;
}
