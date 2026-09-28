<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Decider\Sample;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManager;

final class SampleTokenManagerTest extends TestCase
{
    /**
     * @test
     */
    public function it_validates_its_own_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret');

        self::assertTrue($tokenManager->isValid($tokenManager->create()));
    }

    /**
     * @test
     */
    public function it_rejects_expired_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret', -1);

        self::assertFalse($tokenManager->isValid($tokenManager->create()));
    }

    /**
     * @test
     */
    public function it_rejects_tokens_created_with_another_secret(): void
    {
        self::assertFalse((new SampleTokenManager('secret'))->isValid((new SampleTokenManager('another secret'))->create()));
    }

    /**
     * @test
     */
    public function it_rejects_tampered_and_malformed_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret');
        [, $signature] = explode('.', $tokenManager->create(), 2);

        self::assertFalse($tokenManager->isValid(sprintf('%d.%s', time() + 86400, $signature)), 'Extended expiry');
        self::assertFalse($tokenManager->isValid(''));
        self::assertFalse($tokenManager->isValid('no-dot'));
        self::assertFalse($tokenManager->isValid('abc.' . $signature));
    }
}
