<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverride;
use Symfony\Component\HttpFoundation\Request;

final class ConsentOverrideTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_null_when_the_request_does_not_override_consent(): void
    {
        self::assertNull(ConsentOverride::fromRequest(Request::create('https://example.com/?_consent_expires=1790000000&_consent_signature=abc')));
    }

    /**
     * @test
     */
    public function it_reads_the_override_and_its_signature_from_the_request(): void
    {
        $override = ConsentOverride::fromRequest(Request::create('https://example.com/?_consent[marketing]=1&_consent_expires=1790000000&_consent_signature=abc'));

        self::assertNotNull($override);
        self::assertSame(['marketing' => '1'], $override->consent);
        self::assertSame(1790000000, $override->expires);
        self::assertSame('abc', $override->signature);
    }

    /**
     * @test
     *
     * @dataProvider provideMalformedSignatures
     */
    public function it_ignores_a_malformed_expiry_or_signature(string $query): void
    {
        $override = ConsentOverride::fromRequest(Request::create('https://example.com/?_consent=1&' . $query));

        self::assertNotNull($override);
        self::assertNull($override->expires);
        self::assertNull($override->signature);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideMalformedSignatures(): iterable
    {
        yield 'missing' => [''];
        yield 'not a timestamp' => ['_consent_expires=tomorrow&_consent_signature[]=abc'];
        yield 'negative' => ['_consent_expires=-1&_consent_signature[foo]=abc'];
    }

    /**
     * @test
     *
     * @dataProvider provideCategories
     */
    public function it_tells_whether_it_grants_or_denies_a_category(string $query, string $category, ?bool $expected): void
    {
        $override = ConsentOverride::fromRequest(Request::create('https://example.com/?' . $query));

        self::assertNotNull($override);
        self::assertSame($expected, $override->grants($category));
    }

    /**
     * @return iterable<string, array{string, string, ?bool}>
     */
    public static function provideCategories(): iterable
    {
        yield 'all granted' => ['_consent=1', 'marketing', true];
        yield 'all denied' => ['_consent=0', 'marketing', false];
        yield 'category granted' => ['_consent[marketing]=1', 'marketing', true];
        yield 'category denied' => ['_consent[marketing]=0', 'marketing', false];
        yield 'other category' => ['_consent[statistical]=1', 'marketing', null];
        yield 'not numeric' => ['_consent=yes', 'marketing', null];
        yield 'category not numeric' => ['_consent[marketing][]=1', 'marketing', null];
    }
}
