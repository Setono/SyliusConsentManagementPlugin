<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\ClientId\Generator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\ClientId\Generator\ClientIdGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

final class ClientIdGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates(): void
    {
        $generator = new ClientIdGenerator();
        self::assertIsString($generator->generate());
    }

    /**
     * @test
     */
    public function it_generates_a_new_each_time(): void
    {
        $generator = new ClientIdGenerator();

        $id1 = $generator->generate();
        $id2 = $generator->generate();

        self::assertNotSame($id1, $id2);
    }

    /**
     * @test
     */
    public function it_generates_a_uuid_v4(): void
    {
        $generator = new ClientIdGenerator();

        $uuid = Uuid::fromString($generator->generate());

        self::assertInstanceOf(UuidV4::class, $uuid);
    }
}
