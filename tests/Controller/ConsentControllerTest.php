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
use Setono\SyliusConsentManagementPlugin\Form\Type\CategoryChoiceType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType;
use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Provider\CategoryProviderInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

final class ConsentControllerTest extends TestCase
{
    use ProphecyTrait;

    private const XHR = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

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

        $this->update($this->createRequest($headers, ['functional', 'marketing']), new ConsentEntry());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideCrossSiteHeaders(): iterable
    {
        yield 'cross-site fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'] + self::XHR];
        yield 'same-site fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'same-site'] + self::XHR];
        yield 'foreign origin without fetch metadata' => [['HTTP_ORIGIN' => 'https://evil.example']];
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
        $consentEntry = new ConsentEntry();

        $this->entityManager->persist($consentEntry)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $response = $this->update($this->createRequest($headers, ['functional', 'marketing']), $consentEntry);

        self::assertSame('["necessary","functional","marketing"]', $response);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideSameOriginHeaders(): iterable
    {
        yield 'same-origin fetch metadata' => [['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_ORIGIN' => 'https://shop.example.com'] + self::XHR];
        yield 'XHR without fetch metadata, with an origin host that a proxy misreports' => [['HTTP_ORIGIN' => 'https://www.shop.example.com'] + self::XHR];
        yield 'same origin without fetch metadata' => [['HTTP_ORIGIN' => 'https://shop.example.com']];
        yield 'no fetch metadata or origin' => [[]];
    }

    /**
     * Browsers don't post disabled checkboxes, and the necessary categories are rendered disabled
     *
     * @test
     *
     * @dataProvider provideEmptyBodies
     *
     * @param list<string> $optionalCategories
     */
    public function it_saves_the_necessary_categories_when_the_body_is_empty(array $optionalCategories): void
    {
        $consentEntry = new ConsentEntry();

        $this->entityManager->persist($consentEntry)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $response = $this->update($this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin'] + self::XHR), $consentEntry, $optionalCategories);

        self::assertSame('["necessary"]', $response);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideEmptyBodies(): iterable
    {
        yield '"Accept selected" with nothing ticked' => [['functional', 'marketing']];
        yield '"Accept all" when only necessary categories exist' => [[]];
    }

    /**
     * @test
     *
     * @dataProvider provideMalformedCategories
     */
    public function it_rejects_categories_that_are_not_a_list_of_strings(array|string $consentedCategories): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin'] + self::XHR, $consentedCategories);

        $this->update($request, new ConsentEntry());
    }

    /**
     * @return iterable<string, array{array|string}>
     */
    public static function provideMalformedCategories(): iterable
    {
        yield 'a string' => ['marketing'];
        yield 'a nested array' => [['marketing', ['functional']]];
    }

    /**
     * @test
     */
    public function it_rejects_a_form_that_is_not_an_array(): void
    {
        // The HttpKernel turns a BadRequestException into a 400 response
        $this->expectException(BadRequestException::class);

        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin'] + self::XHR);
        $request->request->set('setono_sylius_consent_management_consent_entry', 'marketing');

        $this->update($request, new ConsentEntry());
    }

    /**
     * @test
     */
    public function it_updates_the_existing_entry_when_a_concurrent_request_created_it_first(): void
    {
        $consentEntry = new ConsentEntry();
        $consentEntry->setClientId('client-id');

        $existingEntry = new ConsentEntry();
        $existingEntry->setClientId('client-id');
        $existingEntry->setConsentedCategories(['necessary']);

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

        $response = $this->update($this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin'] + self::XHR, ['functional', 'marketing']), $consentEntry);

        self::assertSame('["necessary","functional","marketing"]', $response);
        self::assertSame(['necessary', 'functional', 'marketing'], $existingEntry->getConsentedCategories());
        self::assertSame(2, $flushes);
    }

    /**
     * @param array<string, string> $headers
     * @param array|string|null $consentedCategories null for an empty body
     */
    private function createRequest(array $headers, array|string|null $consentedCategories = null): Request
    {
        $parameters = null === $consentedCategories ? [] : [
            'setono_sylius_consent_management_consent_entry' => ['consentedCategories' => $consentedCategories],
        ];

        return Request::create('https://shop.example.com/en_US/ajax/update-consent', 'POST', $parameters, server: $headers);
    }

    /**
     * @param list<string> $optionalCategories
     */
    private function update(Request $request, ConsentEntry $consentEntry, array $optionalCategories = ['functional', 'marketing']): string
    {
        $categories = [self::createCategory('necessary', true)];
        foreach ($optionalCategories as $optionalCategory) {
            $categories[] = self::createCategory($optionalCategory, false);
        }

        $categoryProvider = $this->prophesize(CategoryProviderInterface::class);
        $categoryProvider->getCategories()->willReturn($categories);

        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn($categories);

        $formFactory = Forms::createFormFactoryBuilder()
            ->addTypes([
                new ConsentEntryType($categoryProvider->reveal(), ConsentEntry::class),
                new CategoryChoiceType($categoryRepository->reveal()),
            ])
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
        ;

        $consentEntryTypeFactory = $this->prophesize(ConsentEntryTypeFactoryInterface::class);
        $consentEntryTypeFactory->createNew($request)->willReturn($formFactory->create(ConsentEntryType::class, $consentEntry));

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

    private static function createCategory(string $code, bool $necessary): Category
    {
        $category = new Category();
        $category->setCurrentLocale('en_US');
        $category->setFallbackLocale('en_US');
        $category->setCode($code);
        $category->setName(ucfirst($code));
        $category->setNecessary($necessary);

        return $category;
    }
}
