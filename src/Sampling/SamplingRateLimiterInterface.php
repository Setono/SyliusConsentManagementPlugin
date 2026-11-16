<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Sampling;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * Limits how much one client can make the store record through sampling (server-side sampling and the sample endpoint),
 * so that forged samples can only create a few cookies, and thereby only cause a few notification emails, per client.
 * The crawler isn't limited.
 *
 * A client is identified by its IP address (Request::getClientIp()), so behind a proxy or load balancer, the proxy must
 * be configured as a trusted proxy. Otherwise all visitors share the proxy's IP address, and thereby one limit
 */
interface SamplingRateLimiterInterface
{
    /**
     * Consumes one recording for the client that made the request. If the result isn't accepted, the client has
     * recorded too often recently, and the cookies must not be recorded
     */
    public function consumeRecording(Request $request): RateLimit;

    /**
     * Returns the limiter for the new cookies the client that made the request can introduce. Pass it to the cookie
     * recorder, which consumes a token for each new cookie
     */
    public function createNewCookieLimiter(Request $request): LimiterInterface;
}
