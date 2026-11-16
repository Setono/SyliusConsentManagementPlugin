<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\Client\Client;
use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactory;
use Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntry;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Factory\ConsentEntryFactory
 */
final class ConsentEntryFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_stores_the_referer_without_query_string_for_ajax_requests(): void
    {
        $request = self::createAjaxRequest('https://shop.example.com/en_US/products/mug?gclid=abc#reviews');

        $consentEntry = $this->getFactory()->createFromRequest($request);

        self::assertSame('https://shop.example.com/en_US/products/mug', $consentEntry->getUrl());
    }

    /**
     * @test
     */
    public function it_stores_an_empty_url_when_the_referer_is_on_another_host(): void
    {
        $request = self::createAjaxRequest('https://evil.example/admin-login');

        $consentEntry = $this->getFactory()->createFromRequest($request);

        self::assertSame('', $consentEntry->getUrl());
    }

    /**
     * @test
     */
    public function it_stores_an_empty_url_when_the_referer_is_not_http(): void
    {
        $request = self::createAjaxRequest('javascript:alert(document.domain)');

        $consentEntry = $this->getFactory()->createFromRequest($request);

        self::assertSame('', $consentEntry->getUrl());
    }

    private static function createAjaxRequest(string $referer): Request
    {
        return Request::create('https://shop.example.com/en_US/ajax/update-consent', 'POST', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => $referer,
        ]);
    }

    private function getFactory(): ConsentEntryFactoryInterface
    {
        $categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $categoryRepository->findAll()->willReturn([]);

        $clientContext = $this->prophesize(ClientContextInterface::class);
        $clientContext->getClient()->willReturn(new Client('client-id'));

        return new ConsentEntryFactory(
            new Factory(ConsentEntry::class),
            $categoryRepository->reveal(),
            $clientContext->reveal(),
        );
    }
}
