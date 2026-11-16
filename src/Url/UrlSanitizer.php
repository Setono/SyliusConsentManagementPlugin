<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Url;

use League\Uri\Contracts\UriException;
use League\Uri\Uri;
use Symfony\Component\HttpFoundation\Request;

final class UrlSanitizer
{
    /**
     * Returns the URL of the shop page the visitor was on when the request was made, ready to be stored: the Referer
     * for XHR requests and the request URI otherwise.
     *
     * Returns null unless that URL is an http(s) URL on the request's host. Anyone can send a request with any
     * Referer, and the admin renders cookie URLs as links, so a URL like javascript:alert(1) or one on another site
     * is dropped. The host check relies on framework.trusted_hosts: without it, a request can claim any Host, and a
     * Referer on that host passes.
     *
     * The query string and fragment are removed: they may contain the visitor's tracking ids, and they easily make the
     * URL longer than a database column. Any user info is removed as well. Bytes that aren't allowed in a URL,
     * including invalid UTF-8, are percent-encoded, so the result is plain ASCII.
     */
    public static function sanitizePageUrl(Request $request): ?string
    {
        $url = $request->isXmlHttpRequest() ? $request->headers->get('referer') : $request->getUri();
        if (null === $url) {
            return null;
        }

        try {
            $uri = Uri::createFromString($url);
        } catch (UriException) {
            return null;
        }

        // The scheme and host are lowercased by League\Uri, and Request::getHost() is lowercase as well
        if (!in_array($uri->getScheme(), ['http', 'https'], true) || $uri->getHost() !== $request->getHost()) {
            return null;
        }

        return (string) $uri->withUserInfo(null)->withQuery(null)->withFragment(null);
    }
}
