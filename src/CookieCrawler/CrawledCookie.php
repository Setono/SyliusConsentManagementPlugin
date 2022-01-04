<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\CookieCrawler;

use Symfony\Component\BrowserKit\Cookie;

final class CrawledCookie
{
    public static function fromBrowserKitCookie(Cookie $cookie): self
    {
        return new self();
    }
}
