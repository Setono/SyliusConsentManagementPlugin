<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class CookieFactory implements CookieFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decorated)
    {
    }

    public function createNew(): CookieInterface
    {
        /** @var CookieInterface $obj */
        $obj = $this->decorated->createNew();

        return $obj;
    }

    public function createWithData(string $name, string $url): CookieInterface
    {
        $obj = $this->createNew();
        $obj->setName($name);
        $obj->setUrl($url);

        return $obj;
    }
}
