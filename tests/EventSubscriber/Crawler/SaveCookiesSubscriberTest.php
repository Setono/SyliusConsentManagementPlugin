<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use Facebook\WebDriver\Cookie as WebDriverCookie;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Event\CrawlStarted;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler\SaveCookiesSubscriber;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactory;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Sylius\Component\Channel\Context\CompositeChannelContext;
use Sylius\Component\Core\Model\Channel;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;

final class SaveCookiesSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WebDriverOptions> */
    private ObjectProphecy $browserCookies;

    private Client $client;

    private CrawlerInterface $crawler;

    /** @var \ArrayObject<int, list<CookieInterface>> */
    private \ArrayObject $recordedCookies;

    private SaveCookiesSubscriber $subscriber;

    protected function setUp(): void
    {
        // The cookies of the crawling browser, see Panther's CookieJar
        $this->browserCookies = $this->prophesize(WebDriverOptions::class);

        $webDriver = $this->prophesize(WebDriver::class);
        $webDriver->manage()->willReturn($this->browserCookies->reveal());
        $webDriver->quit()->willReturn(null);

        $browserManager = $this->prophesize(BrowserManagerInterface::class);
        $browserManager->start()->willReturn($webDriver->reveal());
        // Client::__destruct() quits the browser
        $browserManager->quit()->will(static function (): void {
        });

        $this->client = new Client($browserManager->reveal());
        $this->crawler = $this->prophesize(CrawlerInterface::class)->reveal();

        /** @var \ArrayObject<int, list<CookieInterface>> $recordedCookies */
        $recordedCookies = new \ArrayObject();
        $this->recordedCookies = $recordedCookies;

        $cookieRecorder = $this->prophesize(CookieRecorderInterface::class);
        $cookieRecorder->record(Argument::type('array'))->will(static function (array $arguments) use ($recordedCookies): void {
            /** @var list<CookieInterface> $cookies */
            $cookies = $arguments[0];
            $recordedCookies[] = $cookies;
        });

        $this->subscriber = new SaveCookiesSubscriber(
            $cookieRecorder->reveal(),
            new CookieFactory(new Factory(Cookie::class), new CompositeChannelContext(), new RequestStack()),
        );
    }

    /**
     * @test
     */
    public function it_subscribes(): void
    {
        self::assertSame([
            CrawlStarted::class => 'reset',
            Crawled::class => 'save',
        ], SaveCookiesSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_records_the_cookies_of_the_crawled_page(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $expiringCookie = new WebDriverCookie('_ga', 'GA1.1.123');
        $expiringCookie->setExpiry(time() + 86400);
        $this->browserCookies->getCookies()->willReturn([$expiringCookie, new WebDriverCookie('PHPSESSID', 'abc')]);

        $this->subscriber->save($this->createCrawled('https://shop.example/en_US/?gclid=123', $channel));

        $recordedCookies = $this->recordedCookies->getArrayCopy();
        self::assertCount(1, $recordedCookies);
        self::assertCount(2, $recordedCookies[0]);

        [$ga, $session] = $recordedCookies[0];
        self::assertSame('_ga', $ga->getName());
        self::assertSame('https://shop.example/en_US/?gclid=123', $ga->getUrl());
        self::assertFalse($ga->isSession());
        self::assertNotNull($ga->getTtl());
        self::assertSame([$channel], $ga->getChannels()->toArray());

        self::assertSame('PHPSESSID', $session->getName());
        self::assertTrue($session->isSession());
    }

    /**
     * @test
     */
    public function it_records_each_cookie_once_per_crawl(): void
    {
        $this->browserCookies->getCookies()->willReturn([new WebDriverCookie('_ga', '1')], [new WebDriverCookie('_ga', '1'), new WebDriverCookie('_fbp', '2')]);

        // The browser keeps its cookies between the crawled pages
        $this->subscriber->save($this->createCrawled('https://shop.example/'));
        $this->subscriber->save($this->createCrawled('https://shop.example/products/mug'));

        self::assertSame([['_ga'], ['_fbp']], $this->getRecordedNames());
    }

    /**
     * @test
     */
    public function it_records_the_cookies_again_when_a_new_crawl_starts(): void
    {
        $this->browserCookies->getCookies()->willReturn([new WebDriverCookie('_ga', '1')]);

        $this->subscriber->save($this->createCrawled('https://shop.example/'));
        $this->subscriber->reset();
        $this->subscriber->save($this->createCrawled('https://shop.example/'));

        self::assertSame([['_ga'], ['_ga']], $this->getRecordedNames());
    }

    private function createCrawled(string $url, ?Channel $channel = null): Crawled
    {
        return new Crawled($this->crawler, $this->client, new Url($url, $channel));
    }

    /**
     * @return list<list<string|null>>
     */
    private function getRecordedNames(): array
    {
        return array_values(array_map(
            static fn (array $cookies): array => array_map(static fn (CookieInterface $cookie): ?string => $cookie->getName(), $cookies),
            $this->recordedCookies->getArrayCopy(),
        ));
    }
}
