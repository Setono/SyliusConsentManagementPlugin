<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Cookie;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Cookie\CookieName;

final class CookieNameTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideValidNames
     */
    public function it_accepts_valid_names(string $name): void
    {
        self::assertTrue(CookieName::isValid($name));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidNames(): iterable
    {
        yield 'analytics' => ['_ga'];
        yield 'dot' => ['ai_session.v1'];
        yield 'numeric' => ['123'];
        yield 'all token characters' => ["!#$%&'*+-.^_`|~09AZaz"];
        yield 'longest' => [str_repeat('x', 255)];
        // A token can still carry text. That's why names are escaped wherever they're shown
        yield 'text made of token characters' => ['VISIT_evil.example_TO_VERIFY'];
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidNames
     */
    public function it_rejects_invalid_names(string $name): void
    {
        self::assertFalse(CookieName::isValid($name));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'too long for the column' => [str_repeat('x', 256)];
        yield 'spaces' => ['VISIT evil.example TO VERIFY YOUR STORE'];
        yield 'markup' => ['<script>'];
        yield 'brackets' => ['x[y]'];
        yield 'quote' => ['"quoted"'];
        yield 'separator' => ['a;b'];
        yield 'equals' => ['a=b'];
        yield 'non-ASCII' => ['æøå'];
        yield 'line break' => ["_ga\n"];
    }
}
