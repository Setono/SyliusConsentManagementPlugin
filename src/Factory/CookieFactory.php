<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class CookieFactory implements CookieFactoryInterface
{
    private FactoryInterface $decorated;

    public function __construct(FactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function createNew(): CookieInterface
    {
        /** @var CookieInterface $obj */
        $obj = $this->decorated->createNew();

        return $obj;
    }

    public function createWithData(string $name, string $exampleValue, string $url): CookieInterface
    {
        $obj = $this->createNew();
        $obj->setName($name);
        $obj->setExampleValue($exampleValue);
        $obj->setUrl($url);

        return $obj;
    }
}
