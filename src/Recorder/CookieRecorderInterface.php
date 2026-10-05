<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Recorder;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;

/**
 * Records that cookies have been seen, e.g. by sampling a request or by crawling the store
 */
interface CookieRecorderInterface
{
    /**
     * The keys are the cookie names and the values are the cookies to save if no cookie with that name exists yet.
     *
     * Recording never throws. Failures are logged, so that recording cookies can't break the caller. It also doesn't
     * save any other changes the caller has made to entities
     *
     * @param array<string, CookieInterface> $cookies
     */
    public function record(array $cookies): void;
}
