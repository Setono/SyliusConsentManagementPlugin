<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\FormerConsent;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\FormerConsent
 */
final class FormerConsentTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates_and_has_defaults(): void
    {
        $formerConsent = new FormerConsent(true, true, true);

        self::assertTrue($formerConsent->marketingGranted);
        self::assertTrue($formerConsent->preferencesGranted);
        self::assertTrue($formerConsent->statisticsGranted);
        self::assertNull($formerConsent->clientId);
        self::assertNull($formerConsent->url);
        self::assertNull($formerConsent->userAgent);
        self::assertNull($formerConsent->ip);
        self::assertNull($formerConsent->createdAt);
    }
}
