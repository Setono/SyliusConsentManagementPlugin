<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Cookie;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface WidgetCookieManagerInterface
{
    /**
     * Returns true if the cookie exists on the given request
     */
    public function exists(Request $request): bool;

    /**
     * Writes the cookie to the given response
     */
    public function write(Response $response): void;
}
