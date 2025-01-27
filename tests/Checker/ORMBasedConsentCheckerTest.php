<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\Client\Client;
use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Checker\ORMBasedConsentChecker;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Checker\ORMBasedConsentChecker
 */
final class ORMBasedConsentCheckerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ConsentCheckerInterface> */
    private ObjectProphecy $decoratedChecker;

    /** @var ObjectProphecy<RepositoryInterface> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<ClientContextInterface> */
    private ObjectProphecy $clientContext;

    private ORMBasedConsentChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedChecker = $this->prophesize(ConsentCheckerInterface::class);
        $this->repository = $this->prophesize(ConsentEntryRepositoryInterface::class);
        $this->clientContext = $this->prophesize(ClientContextInterface::class);

        $this->checker = new ORMBasedConsentChecker(
            $this->decoratedChecker->reveal(),
            $this->repository->reveal(),
            $this->clientContext->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_returns_true_when_consent_exists(): void
    {
        $client = new Client('client_id');

        $this->clientContext->getClient()->willReturn($client);

        $consentEntry = new ConsentEntry();
        $consentEntry->addConsentedCategory(DefaultConsents::CONSENT_MARKETING);
        $this->repository->findOneFromClient($client)->willReturn($consentEntry);

        self::assertTrue($this->checker->isGranted(DefaultConsents::CONSENT_MARKETING));
    }

    /**
     * @test
     */
    public function it_returns_false_when_consent_does_not_exist(): void
    {
        $client = new Client('client_id');

        $this->clientContext->getClient()->willReturn($client);

        $consentEntry = new ConsentEntry();
        $consentEntry->addConsentedCategory(DefaultConsents::CONSENT_MARKETING);
        $this->repository->findOneFromClient($client)->willReturn($consentEntry);

        self::assertFalse($this->checker->isGranted(DefaultConsents::CONSENT_FUNCTIONAL));
    }
}
