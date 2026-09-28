<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Symfony\Component\HttpFoundation\Request;

final class ConsentOverrideSignerTest extends TestCase
{
    /**
     * @test
     */
    public function it_accepts_its_own_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertTrue($signer->isSigned(self::createRequest($signer->sign('1', new \DateTimeImmutable('+1 hour')))));
        self::assertTrue($signer->isSigned(self::createRequest($signer->sign(['marketing' => '1', 'statistical' => '0'], new \DateTimeImmutable('+1 hour')))));
    }

    /**
     * @test
     */
    public function it_rejects_expired_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->isSigned(self::createRequest($signer->sign('1', new \DateTimeImmutable('-1 second')))));
    }

    /**
     * @test
     */
    public function it_rejects_tampered_overrides(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        $query = $signer->sign(['statistical' => '1'], new \DateTimeImmutable('+1 hour'));
        $query['_consent'] = ['statistical' => '1', 'marketing' => '1'];
        self::assertFalse($signer->isSigned(self::createRequest($query)));

        $query = $signer->sign('1', new \DateTimeImmutable('+1 hour'));
        $query['_consent_expires'] = (string) strtotime('+1 year');
        self::assertFalse($signer->isSigned(self::createRequest($query)));
    }

    /**
     * @test
     */
    public function it_rejects_missing_signatures(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        self::assertFalse($signer->isSigned(self::createRequest(['_consent' => '1'])));
        self::assertFalse($signer->isSigned(self::createRequest(['_consent' => '1', '_consent_expires' => (string) strtotime('+1 hour')])));
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function createRequest(array $query): Request
    {
        // Build the request from the URL, like a real request, so the query values are strings
        return Request::create('https://shop.example.com/en_US/?' . http_build_query($query));
    }
}
