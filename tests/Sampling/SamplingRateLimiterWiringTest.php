<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Sampling;

use Setono\SyliusConsentManagementPlugin\Sampling\SamplingRateLimiterInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The limiters are wired in sampling.xml with cache.app as storage and without a lock factory, so that installs need
 * neither framework.rate_limiter configuration nor symfony/lock
 */
final class SamplingRateLimiterWiringTest extends KernelTestCase
{
    /**
     * @test
     */
    public function it_limits_with_the_application_services_and_default_limits(): void
    {
        self::bootKernel();

        $rateLimiter = self::getContainer()->get(SamplingRateLimiterInterface::class);
        self::assertInstanceOf(SamplingRateLimiterInterface::class, $rateLimiter);

        // cache.app keeps the limits between test runs, so every run is another client
        $request = Request::create('/', server: ['REMOTE_ADDR' => sprintf('2001:db8:%x:%x::1', random_int(0, 0xffff), random_int(0, 0xffff))]);

        foreach (range(1, 10) as $_) {
            self::assertTrue($rateLimiter->consumeRecording($request)->isAccepted());
        }
        self::assertFalse($rateLimiter->consumeRecording($request)->isAccepted());

        self::assertTrue($rateLimiter->createNewCookieLimiter($request)->consume(10)->isAccepted());
        self::assertFalse($rateLimiter->createNewCookieLimiter($request)->consume()->isAccepted());
    }
}
