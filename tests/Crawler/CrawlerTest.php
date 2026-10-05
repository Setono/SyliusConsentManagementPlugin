<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Crawler;

use Facebook\WebDriver\Exception\Internal\WebDriverCurlException;
use Facebook\WebDriver\Exception\InvalidSessionIdException;
use Facebook\WebDriver\Exception\SessionNotCreatedException;
use Facebook\WebDriver\Exception\UnknownErrorException;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverWait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Crawler\Crawler;
use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Event\CrawlStarted;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Setono\SyliusConsentManagementPlugin\Factory\PantherClientFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\UrlProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;
use Tests\Setono\SyliusConsentManagementPlugin\Crawler\Fixture\TestLogger;

final class CrawlerTest extends TestCase
{
    use ProphecyTrait;

    private const DOCUMENT_READY_SCRIPT = 'return document.readyState === "complete";';

    /** @var ObjectProphecy<WebDriver> */
    private ObjectProphecy $webDriver;

    /** @var ObjectProphecy<BrowserManagerInterface> */
    private ObjectProphecy $browserManager;

    /** @var ObjectProphecy<UrlProviderInterface> */
    private ObjectProphecy $urlProvider;

    private EventDispatcher $eventDispatcher;

    /** @var list<string> */
    private array $events = [];

    private TestLogger $logger;

    private Crawler $crawler;

    protected function setUp(): void
    {
        parent::setUp();

        // Panther's Client is final, so a real client is used with a test double of the underlying WebDriver
        $webDriver = $this->webDriver = $this->prophesize(WebDriver::class);
        $this->webDriver->willImplement(JavaScriptExecutor::class);
        $this->webDriver->get(Argument::type('string'))->willReturn($this->webDriver);
        $this->webDriver->getPageSource()->willReturn('<html></html>');
        $this->webDriver->findElements(Argument::any())->willReturn([]);
        $this->webDriver->getCurrentURL()->willReturn('https://shop.example.com/');
        $this->webDriver->executeScript(self::DOCUMENT_READY_SCRIPT, Argument::cetera())->willReturn(true);
        $this->webDriver->wait(Argument::cetera())->will(static fn (): WebDriverWait => new WebDriverWait($webDriver->reveal(), 1));
        $this->webDriver->quit()->willReturn(null);

        $this->browserManager = $this->prophesize(BrowserManagerInterface::class);
        $this->browserManager->start()->willReturn($this->webDriver);
        $this->browserManager->quit()->will(static function (): void {
        });

        $clientFactory = $this->prophesize(PantherClientFactoryInterface::class);
        $clientFactory->create()->willReturn(new Client($this->browserManager->reveal()));

        $this->urlProvider = $this->prophesize(UrlProviderInterface::class);
        $this->urlProvider->getUrls()->willReturn([
            new Url('https://shop.example.com/en_US/'),
            new Url('https://shop.example.com/en_US/products/cap'),
            new Url('https://shop.example.com/en_US/products/mug'),
        ]);

        $this->events = [];
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(CrawlStarted::class, function (): void {
            $this->events[] = 'crawl started';
        });
        $this->eventDispatcher->addListener(WillCrawl::class, function (WillCrawl $event): void {
            $this->events[] = 'will crawl ' . (string) $event->url;
        });
        $this->eventDispatcher->addListener(Crawled::class, function (Crawled $event): void {
            $this->events[] = 'crawled ' . (string) $event->url;
        });

        $this->logger = new TestLogger();

        $this->crawler = new Crawler($clientFactory->reveal(), $this->eventDispatcher, $this->urlProvider->reveal(), [
            'request_delay' => 0,
            'wait_for_document_ready' => 1,
        ]);
        $this->crawler->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_crawls_every_url_and_quits_the_browser(): void
    {
        $this->webDriver->executeScript(self::DOCUMENT_READY_SCRIPT, Argument::cetera())->willReturn(true)->shouldBeCalledTimes(3);
        $this->webDriver->quit()->shouldBeCalled();
        $this->browserManager->quit()->shouldBeCalled();

        $result = $this->crawler->start();

        self::assertSame(3, $result->crawled);
        self::assertSame(0, $result->failed);
        self::assertSame([
            'crawl started',
            'will crawl https://shop.example.com/en_US/',
            'crawled https://shop.example.com/en_US/',
            'will crawl https://shop.example.com/en_US/products/cap',
            'crawled https://shop.example.com/en_US/products/cap',
            'will crawl https://shop.example.com/en_US/products/mug',
            'crawled https://shop.example.com/en_US/products/mug',
        ], $this->events);
        self::assertSame([], $this->logger->errors());
        self::assertContains('info: Crawling https://shop.example.com/en_US/products/cap', $this->logger->logs);
        self::assertContains('info: Will wait a maximum of 1 seconds document ready', $this->logger->logs);
        self::assertSame('info: Crawled 3 URL(s), 0 failed', $this->logger->lastLog());
    }

    /**
     * @test
     */
    public function it_continues_crawling_when_a_url_fails_to_load(): void
    {
        // This is how Chrome reports e.g. a refused connection
        $this->webDriver->get('https://shop.example.com/en_US/products/cap')->willThrow(new UnknownErrorException('unknown error: net::ERR_CONNECTION_REFUSED'));
        $this->webDriver->quit()->shouldBeCalled();
        $this->browserManager->quit()->shouldBeCalled();

        $result = $this->crawler->start();

        self::assertSame(2, $result->crawled);
        self::assertSame(1, $result->failed);
        self::assertSame([
            'crawl started',
            'will crawl https://shop.example.com/en_US/',
            'crawled https://shop.example.com/en_US/',
            'will crawl https://shop.example.com/en_US/products/cap',
            'will crawl https://shop.example.com/en_US/products/mug',
            'crawled https://shop.example.com/en_US/products/mug',
        ], $this->events);
        self::assertSame(['error: Failed to crawl https://shop.example.com/en_US/products/cap. Error was: unknown error: net::ERR_CONNECTION_REFUSED'], $this->logger->errors());
        self::assertSame('warning: Crawled 2 URL(s), 1 failed', $this->logger->lastLog());
    }

    /**
     * @test
     */
    public function it_continues_crawling_when_the_document_is_not_ready_in_time(): void
    {
        $webDriver = $this->webDriver->reveal();

        // A wait without any time left times out right away, like a document that never gets ready
        $this->webDriver->wait(Argument::cetera())->willReturn(
            new WebDriverWait($webDriver, 1),
            new WebDriverWait($webDriver, 0),
            new WebDriverWait($webDriver, 1),
        );
        $this->webDriver->executeScript(self::DOCUMENT_READY_SCRIPT, Argument::cetera())->willReturn(true)->shouldBeCalledTimes(2);

        $result = $this->crawler->start();

        self::assertSame(2, $result->crawled);
        self::assertSame(1, $result->failed);
        self::assertSame([
            'crawl started',
            'will crawl https://shop.example.com/en_US/',
            'crawled https://shop.example.com/en_US/',
            'will crawl https://shop.example.com/en_US/products/cap',
            'will crawl https://shop.example.com/en_US/products/mug',
            'crawled https://shop.example.com/en_US/products/mug',
        ], $this->events);
        self::assertSame(['error: Failed to crawl https://shop.example.com/en_US/products/cap. Error was: The document was not ready after 1 seconds'], $this->logger->errors());
    }

    /**
     * @test
     *
     * @dataProvider provideBrowserSessionErrors
     */
    public function it_aborts_the_crawl_when_the_browser_session_is_lost(\Throwable $error): void
    {
        $this->webDriver->get('https://shop.example.com/en_US/products/cap')->willThrow($error);
        $this->webDriver->get('https://shop.example.com/en_US/products/mug')->shouldNotBeCalled();

        // Quitting a browser that is gone fails too, which must not hide why the crawl was aborted
        $quitCalls = 0;
        $this->webDriver->quit()->will(static function () use (&$quitCalls, $error): void {
            if (1 === ++$quitCalls) {
                throw $error;
            }
        });

        $exception = $this->startUntilAborted();

        self::assertInstanceOf(\RuntimeException::class, $exception);
        self::assertSame(sprintf(
            'Aborted the crawl, because the browser session was lost while crawling https://shop.example.com/en_US/products/cap. Error was: %s',
            $error->getMessage(),
        ), $exception->getMessage());
        self::assertSame($error, $exception->getPrevious());
        self::assertSame(1, $quitCalls);
        self::assertSame([
            'crawl started',
            'will crawl https://shop.example.com/en_US/',
            'crawled https://shop.example.com/en_US/',
            'will crawl https://shop.example.com/en_US/products/cap',
        ], $this->events);
        self::assertSame([], $this->logger->errors());
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function provideBrowserSessionErrors(): iterable
    {
        yield 'the browser closed the session' => [new InvalidSessionIdException('invalid session id: session deleted as the browser has closed the connection')];
        yield 'chromedriver is gone' => [new WebDriverCurlException('Curl error thrown for http GET to /session/abc/url')];
    }

    /**
     * @test
     */
    public function it_does_not_catch_exceptions_from_crawled_listeners(): void
    {
        $exception = new \RuntimeException('The EntityManager is closed.');
        $this->eventDispatcher->addListener(Crawled::class, static function () use ($exception): void {
            throw $exception;
        });
        $this->webDriver->get('https://shop.example.com/en_US/products/cap')->shouldNotBeCalled();
        $this->webDriver->quit()->shouldBeCalled();
        $this->browserManager->quit()->shouldBeCalled();

        self::assertSame($exception, $this->startUntilAborted());
        self::assertSame([
            'crawl started',
            'will crawl https://shop.example.com/en_US/',
            'crawled https://shop.example.com/en_US/',
        ], $this->events);
        self::assertSame([], $this->logger->errors());
    }

    /**
     * @test
     */
    public function it_aborts_the_crawl_when_the_browser_does_not_start(): void
    {
        $exception = new SessionNotCreatedException('session not created: This version of ChromeDriver only supports Chrome version 120');
        $this->browserManager->start()->willThrow($exception);
        $this->browserManager->quit()->shouldBeCalled();
        $this->urlProvider->getUrls()->shouldNotBeCalled();

        self::assertSame($exception, $this->startUntilAborted());
        self::assertSame([], $this->events);
    }

    /**
     * Returns the exception that aborted the crawl
     */
    private function startUntilAborted(): \Throwable
    {
        try {
            $result = $this->crawler->start();
        } catch (\Throwable $e) {
            return $e;
        }

        self::fail(sprintf('The crawl was not aborted. %d URL(s) were crawled and %d failed', $result->crawled, $result->failed));
    }
}
