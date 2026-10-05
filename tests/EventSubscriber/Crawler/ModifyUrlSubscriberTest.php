<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler\ModifyUrlSubscriber;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;

final class ModifyUrlSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_a_signed_consent_override_and_disables_sampling(): void
    {
        $signer = new ConsentOverrideSigner('secret');

        $event = $this->createEvent();
        (new ModifyUrlSubscriber($signer))->modify($event);

        $request = Request::create((string) $event->url);

        self::assertSame('/en_US/products/mug', $request->getPathInfo());
        self::assertSame('abc', $request->query->get('gclid'));
        self::assertSame('1', $request->query->get('_consent'));
        self::assertSame('0', $request->query->get('_sample'));
        self::assertTrue($signer->isSigned($request));
    }

    /**
     * @test
     */
    public function it_signs_the_consent_override_for_a_few_minutes_only(): void
    {
        $event = $this->createEvent();
        (new ModifyUrlSubscriber(new ConsentOverrideSigner('secret')))->modify($event);

        $expires = (int) Request::create((string) $event->url)->query->get('_consent_expires');

        self::assertGreaterThan(time(), $expires);
        self::assertLessThanOrEqual(time() + 300, $expires);
    }

    /**
     * @test
     */
    public function it_ignores_the_arg_separator_output_setting(): void
    {
        $signer = new ConsentOverrideSigner('secret');
        $event = $this->createEvent();

        $argSeparator = ini_set('arg_separator.output', '&amp;');

        try {
            (new ModifyUrlSubscriber($signer))->modify($event);
        } finally {
            ini_set('arg_separator.output', (string) $argSeparator);
        }

        $request = Request::create((string) $event->url);

        self::assertSame('0', $request->query->get('_sample'));
        self::assertTrue($signer->isSigned($request));
    }

    private function createEvent(): WillCrawl
    {
        return new WillCrawl(
            $this->prophesize(CrawlerInterface::class)->reveal(),
            new Client($this->prophesize(BrowserManagerInterface::class)->reveal()),
            new Url('https://shop.example.com/en_US/products/mug?gclid=abc'),
        );
    }
}
