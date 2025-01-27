<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Checker\ORMBasedConsentChecker;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Checker\ORMBasedConsentChecker
 */
final class ORMBasedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_decorated_consent_if_no_consent_has_been_saved(): void
    {
        $context = new ORMBasedConsentChecker(self::getConsentContext(), $this->getRepository());
        $consent = $context->getConsent();

        self::assertSame('client_id', $consent->getClientId()->toString());
    }

    /**
     * @test
     */
    public function it_returns_saved_consent(): void
    {
        $context = new ORMBasedConsentChecker(
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
