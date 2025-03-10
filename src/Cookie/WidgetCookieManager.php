<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WidgetCookieManager implements WidgetCookieManagerInterface
{
    public function __construct(
        private readonly string $cookieName,
        private readonly string $cookieValue,
    ) {
    }

    public function exists(Request $request): bool
    {
        return $request->cookies->has($this->cookieName) && $request->cookies->get($this->cookieName) === $this->cookieValue;
    }

    public function write(Response $response): void
    {
        $response->headers->setCookie(Cookie::create(
            name: $this->cookieName,
            value: $this->cookieValue,
            expire: new \DateTime('+365 days'),
            httpOnly: false,
        ));
    }
}
