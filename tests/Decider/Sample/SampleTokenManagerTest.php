<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Decider\Sample;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SampleTokenManagerTest extends TestCase
{
    /**
     * @test
     */
    public function it_accepts_its_own_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret', new ArrayAdapter());

        self::assertTrue($tokenManager->consume($tokenManager->create()));
        self::assertTrue($tokenManager->consume($tokenManager->create()), 'Every token is new');
    }

    /**
     * @test
     */
    public function it_accepts_a_token_only_once(): void
    {
        $usedTokens = new ArrayAdapter();
        $token = (new SampleTokenManager('secret', $usedTokens))->create();

        self::assertTrue((new SampleTokenManager('secret', $usedTokens))->consume($token));
        self::assertFalse((new SampleTokenManager('secret', $usedTokens))->consume($token), 'E.g. in the next request');
    }

    /**
     * @test
     */
    public function it_rejects_expired_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret', new ArrayAdapter(), -1);

        self::assertFalse($tokenManager->consume($tokenManager->create()));
    }

    /**
     * @test
     */
    public function it_rejects_tokens_created_with_another_secret(): void
    {
        $token = (new SampleTokenManager('another secret', new ArrayAdapter()))->create();

        self::assertFalse((new SampleTokenManager('secret', new ArrayAdapter()))->consume($token));
    }

    /**
     * @test
     */
    public function it_rejects_tampered_and_malformed_tokens(): void
    {
        $tokenManager = new SampleTokenManager('secret', new ArrayAdapter());
        [, $nonce, $signature] = explode('.', $tokenManager->create());

        self::assertFalse($tokenManager->consume(sprintf('%d.%s.%s', time() + 86400, $nonce, $signature)), 'Extended expiry');
        self::assertFalse($tokenManager->consume(sprintf('%d.%s.%s', time() + 60, str_repeat('0', 32), $signature)), 'Another nonce');
        self::assertFalse($tokenManager->consume(''));
        self::assertFalse($tokenManager->consume('no-dot'));
        self::assertFalse($tokenManager->consume('abc.' . $nonce . '.' . $signature));
        self::assertFalse($tokenManager->consume(sprintf('%d.%s', time() + 60, $signature)), 'Without a nonce');
    }
}
