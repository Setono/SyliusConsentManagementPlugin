<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConsentWidgetCookieManager implements ConsentWidgetCookieManagerInterface
{
    public function __construct(
        private readonly string $cookieName,
    ) {
    }

    public function exists(Request $request): bool
    {
        return $request->cookies->has($this->cookieName);
    }

    public function write(Response $response): void
    {
        $response->headers->setCookie(Cookie::create(
            $this->cookieName,
            '1',
            new \DateTime('+365 days'),
            '/',
            null,
            null,
            false,
        ));
    }
}
