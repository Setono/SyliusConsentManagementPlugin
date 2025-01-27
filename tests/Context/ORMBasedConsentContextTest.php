<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Context;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\ClientId\ClientId;
use Setono\ClientId\Provider\ClientIdProviderInterface;
use Setono\Consent\Consent;
use Setono\Consent\Context\ConsentContextInterface;
use Setono\Consent\Context\DefaultConsentContext;
use Setono\SyliusConsentManagementPlugin\Context\ORMBasedConsentContext;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Context\ORMBasedConsentContext
 */
final class ORMBasedConsentContextTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_decorated_consent_if_no_consent_has_been_saved(): void
    {
        $context = new ORMBasedConsentContext(self::getConsentContext(), $this->getRepository());
        $consent = $context->getConsent();

        self::assertSame('client_id', $consent->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_returns_saved_consent(): void
    {
        $context = new ORMBasedConsentContext(
            self::getConsentContext(),
            $this->getRepository(new Consent(new ClientId('saved_client_id'), false, false, false)),
        );
        $consent = $context->getConsent();

        self::assertSame('saved_client_id', $consent->getClientId()->toString());
    }

    private static function getConsentContext(): ConsentContextInterface
    {
        $clientIdProvider = new class() implements ClientIdProviderInterface {
            public function getClientId(): ClientId
            {
                return new ClientId('client_id');
            }
        };

        return new DefaultConsentContext($clientIdProvider);
    }

    private function getRepository(Consent $consent = null): ConsentEntryRepositoryInterface
    {
        $repository = $this->prophesize(ConsentEntryRepositoryInterface::class);
        $repository->findFromClient(Argument::any())->willReturn($consent);

        return $repository->reveal();
    }
}
