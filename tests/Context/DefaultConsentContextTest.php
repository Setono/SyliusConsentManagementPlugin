<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\SyliusConsentManagementPlugin\Context\DefaultConsentContext;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Context\DefaultConsentContext
 */
final class DefaultConsentContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_default_consent(): void
    {
        $context = new DefaultConsentContext(new class() implements ClientIdProviderInterface {
            public function getClientId(): ClientId
            {
                return new ClientId('client_id');
            }
        });

        self::assertSame('client_id', $context->getConsent()->getClientId()->toString());
    }
}
