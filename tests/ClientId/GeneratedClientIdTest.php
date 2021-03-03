<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\ClientId;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\ClientId\GeneratedClientId;
use Setono\SyliusConsentManagementPlugin\ClientId\Generator\ClientIdGeneratorInterface;

final class GeneratedClientIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_generator(): void
    {
        $generator = new class() implements ClientIdGeneratorInterface {
            public function generate(): string
            {
                return 'client_id';
            }
        };

        $clientId = new GeneratedClientId($generator);
        self::assertSame('client_id', $clientId->get());
    }
}
