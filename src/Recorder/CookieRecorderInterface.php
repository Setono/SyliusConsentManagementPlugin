<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Recorder;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Symfony\Component\RateLimiter\LimiterInterface;

/**
 * Records that cookies have been seen, e.g. by sampling a request or by crawling the store
 */
interface CookieRecorderInterface
{
    /**
     * Each cookie is identified by its name, and is saved if no cookie with that name exists yet. Cookies with the same
     * name are recorded once, as the first of them. Names that can't be cookie names are skipped, and the number of
     * cookies recorded per call is limited, because the names can come from untrusted input (a request's Cookie header
     * or the sample endpoint).
     *
     * With a limiter for new cookies, e.g. per client, each new cookie consumes one of its tokens. New cookies beyond the
     * available tokens are skipped, while existing cookies are always recorded.
     *
     * Recording never throws. Failures are logged, so that recording cookies can't break the caller. It also doesn't
     * save any other changes the caller has made to entities
     *
     * @param iterable<CookieInterface> $cookies
     */
    public function record(iterable $cookies, ?LimiterInterface $newCookieLimiter = null): void;
}
