<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Cookie;

/**
 * The rule for which cookie names the plugin records, whichever source they come from: server-side sampling, the
 * sample endpoint or the crawler
 */
final class CookieName
{
    /**
     * The length of the name column, see Resources/config/doctrine/model/Cookie.orm.xml
     */
    private const MAX_LENGTH = 255;

    /**
     * The characters allowed in a cookie name (a 'token' in RFC 6265). This keeps out names with spaces, quotes or
     * markup, like 'VISIT evil.example TO VERIFY YOUR STORE' or '<script>'. It doesn't keep out text, though: '.', '_'
     * and '-' are token characters, so 'VISIT_evil.example_TO_VERIFY' is a valid name. Names stay untrusted input
     * wherever they're shown, e.g. in the admin's cookie list and in the notification email, which escape them.
     *
     * The D modifier makes $ match only at the very end, not before a trailing line break
     */
    private const PATTERN = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/D';

    private function __construct()
    {
    }

    /**
     * @phpstan-assert-if-true non-empty-string $name
     */
    public static function isValid(string $name): bool
    {
        return strlen($name) <= self::MAX_LENGTH && 1 === preg_match(self::PATTERN, $name);
    }
}
