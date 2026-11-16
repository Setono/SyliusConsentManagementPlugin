<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Sampling;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Sampling\SamplingRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class SamplingRateLimiterTest extends TestCase
{
    /**
     * @test
     */
    public function it_limits_the_recordings_per_client(): void
    {
        $rateLimiter = self::createRateLimiter(recordings: 2);

        self::assertTrue($rateLimiter->consumeRecording(self::createRequest('192.0.2.1'))->isAccepted());
        self::assertTrue($rateLimiter->consumeRecording(self::createRequest('192.0.2.1'))->isAccepted());
        self::assertFalse($rateLimiter->consumeRecording(self::createRequest('192.0.2.1'))->isAccepted());

        self::assertTrue($rateLimiter->consumeRecording(self::createRequest('192.0.2.2'))->isAccepted(), 'Another client');
    }

    /**
     * @test
     */
    public function it_limits_the_new_cookies_per_client(): void
    {
        $rateLimiter = self::createRateLimiter(newCookies: 3);

        self::assertTrue($rateLimiter->createNewCookieLimiter(self::createRequest('192.0.2.1'))->consume(3)->isAccepted());
        self::assertFalse($rateLimiter->createNewCookieLimiter(self::createRequest('192.0.2.1'))->consume()->isAccepted());

        self::assertTrue($rateLimiter->createNewCookieLimiter(self::createRequest('192.0.2.2'))->consume()->isAccepted(), 'Another client');
    }

    /**
     * @test
     */
    public function it_limits_an_ipv6_client_by_its_64_network(): void
    {
        $rateLimiter = self::createRateLimiter(recordings: 1);

        self::assertTrue($rateLimiter->consumeRecording(self::createRequest('2001:db8:1:2::1'))->isAccepted());
        self::assertFalse($rateLimiter->consumeRecording(self::createRequest('2001:db8:1:2:ffff:ffff:ffff:fffe'))->isAccepted(), 'Another address in the same /64 network');

        self::assertTrue($rateLimiter->consumeRecording(self::createRequest('2001:db8:1:3::1'))->isAccepted(), 'Another /64 network');
    }

    /**
     * @test
     */
    public function it_accepts_everything_when_disabled(): void
    {
        $rateLimiter = new SamplingRateLimiter(
            new RateLimiterFactory(['id' => 'recordings', 'policy' => 'no_limit'], new InMemoryStorage()),
            new RateLimiterFactory(['id' => 'new_cookies', 'policy' => 'no_limit'], new InMemoryStorage()),
        );

        foreach (range(1, 100) as $_) {
            self::assertTrue($rateLimiter->consumeRecording(self::createRequest('192.0.2.1'))->isAccepted());
        }

        self::assertTrue($rateLimiter->createNewCookieLimiter(self::createRequest('192.0.2.1'))->consume(50)->isAccepted());
    }

    private static function createRateLimiter(int $recordings = 10, int $newCookies = 10): SamplingRateLimiter
    {
        return new SamplingRateLimiter(
            new RateLimiterFactory(['id' => 'recordings', 'policy' => 'sliding_window', 'limit' => $recordings, 'interval' => '1 hour'], new InMemoryStorage()),
            new RateLimiterFactory(['id' => 'new_cookies', 'policy' => 'sliding_window', 'limit' => $newCookies, 'interval' => '1 day'], new InMemoryStorage()),
        );
    }

    private static function createRequest(string $clientIp): Request
    {
        return Request::create('/', server: ['REMOTE_ADDR' => $clientIp]);
    }
}
