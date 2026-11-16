<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Sampling;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class SamplingRateLimiter implements SamplingRateLimiterInterface
{
    public function __construct(
        private readonly RateLimiterFactory $recordingLimiterFactory,
        private readonly RateLimiterFactory $newCookieLimiterFactory,
    ) {
    }

    public function consumeRecording(Request $request): RateLimit
    {
        return $this->recordingLimiterFactory->create(self::getClientKey($request))->consume();
    }

    public function createNewCookieLimiter(Request $request): LimiterInterface
    {
        return $this->newCookieLimiterFactory->create(self::getClientKey($request));
    }

    private static function getClientKey(Request $request): string
    {
        $ip = $request->getClientIp() ?? '';

        // An IPv6 client usually gets a whole /64 network, so it could use a new address for every request
        if (str_contains($ip, ':')) {
            $packedIp = @inet_pton($ip);
            if (false !== $packedIp) {
                return bin2hex(substr($packedIp, 0, 8)) . '/64';
            }
        }

        return $ip;
    }
}
