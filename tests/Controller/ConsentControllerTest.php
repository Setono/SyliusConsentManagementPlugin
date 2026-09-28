<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Controller;

use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusConsentManagementPlugin\Controller\ConsentController;
use Setono\SyliusConsentManagementPlugin\Cookie\WidgetCookieManagerInterface;
use Setono\SyliusConsentManagementPlugin\Form\Factory\ConsentEntryTypeFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ConsentControllerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(ConsentEntry::class)->willReturn($this->entityManager);
        $this->managerRegistry->getManagers()->willReturn(['default' => $this->entityManager]);
    }

    /**
     * @test
     *
     * @dataProvider provideCrossSiteHeaders
     *
     * @param array<string, string> $headers
     */
    public function it_rejects_cross_site_requests(array $headers): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->update($this->createRequest($headers), $this->createConsentEntry(['functional', 'marketing']));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideCrossSiteHeaders(): iterable
    {
        yield 'cross-site fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'cross-site']];
        yield 'same-site fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'same-site']];
        yield 'foreign origin' => [['HTTP_ORIGIN' => 'https://evil.example']];
    }

    /**
     * @test
     *
     * @dataProvider provideSameOriginHeaders
     *
     * @param array<string, string> $headers
     */
    public function it_saves_the_consent_for_same_origin_requests(array $headers): void
    {
        $consentEntry = $this->createConsentEntry(['functional', 'marketing']);

        $this->entityManager->persist($consentEntry)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $response = $this->update($this->createRequest($headers), $consentEntry);

        self::assertSame('["functional","marketing"]', $response);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideSameOriginHeaders(): iterable
    {
        yield 'same-origin fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_ORIGIN' => 'https://shop.example.com']];
        yield 'same origin without fetch metadata' => [['HTTP_ORIGIN' => 'https://shop.example.com']];
        yield 'no fetch metadata or origin' => [[]];
    }

    /**
     * @test
     */
    public function it_updates_the_existing_entry_when_a_concurrent_request_created_it_first(): void
    {
        $consentEntry = $this->createConsentEntry(['functional', 'marketing']);
        $existingEntry = $this->createConsentEntry(['functional']);

        $flushes = 0;
        $this->entityManager->persist($consentEntry)->shouldBeCalledOnce();
        $this->entityManager->flush()->will(function () use (&$flushes): void {
            if (1 === ++$flushes) {
                throw new UniqueConstraintViolationException(new class('Duplicate entry') extends AbstractException {
                }, null);
            }
        });

        $repository = $this->prophesize(EntityRepository::class);
        $repository->findOneBy(['clientId' => 'client-id'])->willReturn($existingEntry);
        $this->entityManager->getRepository(ConsentEntry::class)->willReturn($repository);
        $this->managerRegistry->resetManager('default')->shouldBeCalledOnce()->willReturn($this->entityManager);

        $response = $this->update($this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin']), $consentEntry);

        self::assertSame('["functional","marketing"]', $response);
        self::assertSame(['functional', 'marketing'], $existingEntry->getConsentedCategories());
        self::assertSame(2, $flushes);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createRequest(array $headers): Request
    {
        return Request::create('https://shop.example.com/en_US/ajax/update-consent', 'POST', server: $headers + ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    }

    /**
     * @param list<string> $categories
     */
    private function createConsentEntry(array $categories): ConsentEntry
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->setClientId('client-id');
        $consentEntry->setConsentedCategories($categories);

        return $consentEntry;
    }

    private function update(Request $request, ConsentEntry $submittedEntry): string
    {
        $form = $this->prophesize(FormInterface::class);
        $form->handleRequest($request)->willReturn($form);
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($submittedEntry);

        $consentEntryTypeFactory = $this->prophesize(ConsentEntryTypeFactoryInterface::class);
        $consentEntryTypeFactory->createNew($request)->willReturn($form);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->willReturnArgument(0);

        $controller = new ConsentController($this->managerRegistry->reveal());

        return (string) $controller->update(
            $request,
            $this->prophesize(WidgetCookieManagerInterface::class)->reveal(),
            $consentEntryTypeFactory->reveal(),
            $eventDispatcher->reveal(),
        )->getContent();
    }
}
