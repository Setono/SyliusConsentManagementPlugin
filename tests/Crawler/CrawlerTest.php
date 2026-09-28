<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Crawler;

use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverWait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\AbstractLogger;
use Setono\SyliusConsentManagementPlugin\Crawler\Crawler;
use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Factory\PantherClientFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\UrlProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;

final class CrawlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_continues_crawling_when_a_url_fails_and_quits_the_browser(): void
    {
        // Panther's Client is final, so a real client is used with a test double of the underlying WebDriver
        $webDriver = $this->prophesize(WebDriver::class);
        $webDriver->willImplement(JavaScriptExecutor::class);
        $webDriver->get('https://shop.example.com/en_US/')->willReturn($webDriver);
        $webDriver->get('https://shop.example.com/broken')->willThrow(new \RuntimeException('net::ERR_CONNECTION_REFUSED'));
        $webDriver->get('https://shop.example.com/en_US/products/mug')->willReturn($webDriver);
        $webDriver->getPageSource()->willReturn('<html></html>');
        $webDriver->findElements(Argument::any())->willReturn([]);
        $webDriver->getCurrentURL()->willReturn('https://shop.example.com/');
        $webDriver->executeScript(Argument::cetera())->willReturn(true);
        $webDriver->wait(Argument::cetera())->will(static fn (): WebDriverWait => new WebDriverWait($webDriver->reveal(), 1));
        $webDriver->quit()->shouldBeCalled();

        $browserManager = $this->prophesize(BrowserManagerInterface::class);
        $browserManager->start()->willReturn($webDriver);
        $browserManager->quit()->shouldBeCalled();

        $clientFactory = $this->prophesize(PantherClientFactoryInterface::class);
        $clientFactory->create()->willReturn(new Client($browserManager->reveal()));

        $urlProvider = $this->prophesize(UrlProviderInterface::class);
        $urlProvider->getUrls()->willReturn([
            new Url('https://shop.example.com/en_US/'),
            new Url('https://shop.example.com/broken'),
            new Url('https://shop.example.com/en_US/products/mug'),
        ]);

        $crawledUrls = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(Crawled::class, static function (Crawled $event) use (&$crawledUrls): void {
            $crawledUrls[] = (string) $event->url;
        });

        $logger = new class() extends AbstractLogger {
            /** @var list<string> */
            public array $errors = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                if ('error' === $level) {
                    $this->errors[] = strtr((string) $message, array_filter(['{url}' => $context['url'] ?? null, '{error}' => $context['error'] ?? null], 'is_string'));
                }
            }
        };

        $crawler = new Crawler($clientFactory->reveal(), $eventDispatcher, $urlProvider->reveal(), [
            'request_delay' => 0,
            'wait_for_document_ready' => 1,
        ]);
        $crawler->setLogger($logger);
        $crawler->start();

        self::assertSame([
            'https://shop.example.com/en_US/',
            'https://shop.example.com/en_US/products/mug',
        ], $crawledUrls, implode("\n", $logger->errors));
        self::assertCount(1, $logger->errors);
        self::assertStringContainsString('https://shop.example.com/broken', $logger->errors[0]);
    }
}
