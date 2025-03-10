<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;

interface CookieFactoryInterface extends FactoryInterface
{
    public function createNew(): CookieInterface;

    public function createWithName(string $name): CookieInterface;

    /**
     * @throws \InvalidArgumentException if the $sample is not the expected shape
     */
    public function createFromSample(mixed $sample): CookieInterface;

    public function createFromBrowserKitCookie(BrowserKitCookie $cookie, Url $url): CookieInterface;
}
