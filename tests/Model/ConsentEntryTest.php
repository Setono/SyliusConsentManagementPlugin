<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Controller\Action\ConsentCommand;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Model\ConsentEntry
 */
final class ConsentEntryTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_and_gets(): void
    {
        $clientId = new ClientId('client_id');

        $consentEntry = new ConsentEntry();
        $consentEntry->setClientId($clientId);
        $consentEntry->setUserAgent('user agent');
        $consentEntry->setUrl('https://example.com');
        $consentEntry->setIp('192.168.1.1');
        $consentEntry->setMarketingGranted(true);
        $consentEntry->setStatisticsGranted(true);
        $consentEntry->setPreferencesGranted(true);

        self::assertNull($consentEntry->getId());
        self::assertSame($clientId, $consentEntry->getClientId());
        self::assertSame('user agent', $consentEntry->getUserAgent());
        self::assertSame('https://example.com', $consentEntry->getUrl());
        self::assertSame('192.168.1.1', $consentEntry->getIp());
        self::assertTrue($consentEntry->isMarketingGranted());
        self::assertTrue($consentEntry->isStatisticsGranted());
        self::assertTrue($consentEntry->isPreferencesGranted());
    }

    /**
     * @test
     */
    public function it_populates_from_request(): void
    {
        $request = new class() extends Request {
            public function __construct(
            ) {
                parent::__construct();

                $this->headers->set('user-agent', 'user agent');
            }

            public function getClientIp(): string
            {
                return '192.168.1.1';
            }

            public function getUri(): string
            {
                return 'https://example.com';
            }
        };

        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromRequest($request);

        self::assertSame('192.168.1.1', $consentEntry->getIp());
        self::assertSame('https://example.com', $consentEntry->getUrl());
        self::assertSame('user agent', $consentEntry->getUserAgent());
    }

    /**
     * @test
     */
    public function it_populates_from_request_and_handles_nullable_values(): void
    {
        $request = new class() extends Request {
            public function __construct(
            ) {
                parent::__construct();

                $this->headers->set('user-agent', 'user agent');
            }

            public function getClientIp(): ?string
            {
                return null;
            }

            public function getUri(): string
            {
                return 'https://example.com';
            }
        };

        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromRequest($request);

        self::assertSame('', $consentEntry->getIp());
    }

    /**
     * @test
     */
    public function it_populates_from_xml_http_request(): void
    {
        $request = new class() extends Request {
            public function __construct(
            ) {
                parent::__construct();

                $this->headers->set('referer', 'https://example.com');
            }

            public function isXmlHttpRequest(): bool
            {
                return true;
            }
        };

        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromRequest($request);

        self::assertSame('https://example.com', $consentEntry->getUrl());
    }

    /**
     * @test
     */
    public function it_does_not_populate_referrer_if_request_is_not_xml_http_request(): void
    {
        $request = new class() extends Request {
            public function __construct(
            ) {
                parent::__construct();

                $this->headers->set('referer', 'https://example.com');
            }

            public function isXmlHttpRequest(): bool
            {
                return false;
            }

            public function getUri(): string
            {
                return 'https://not-referrer.com';
            }
        };

        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromRequest($request);

        self::assertSame('https://not-referrer.com', $consentEntry->getUrl());
    }

    /**
     * @test
     */
    public function it_populates_from_consent_command(): void
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->populateFromConsentCommand(new ConsentCommand());

        self::assertTrue($consentEntry->isMarketingGranted());
        self::assertTrue($consentEntry->isStatisticsGranted());
        self::assertTrue($consentEntry->isPreferencesGranted());
    }
}
