<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Command\CrawlCommand;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlResult;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

final class CrawlCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CrawlerInterface> */
    private ObjectProphecy $crawler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crawler = $this->prophesize(CrawlerInterface::class);
    }

    /**
     * @test
     */
    public function it_succeeds_when_every_url_was_crawled(): void
    {
        $this->crawler->start()->willReturn(new CrawlResult(3, 0));

        self::assertSame(Command::SUCCESS, $this->runCommand());
    }

    /**
     * @test
     */
    public function it_fails_when_a_url_failed_to_load(): void
    {
        $this->crawler->start()->willReturn(new CrawlResult(2, 1));

        self::assertSame(Command::FAILURE, $this->runCommand());
    }

    /**
     * @test
     */
    public function it_fails_when_the_crawl_was_aborted(): void
    {
        $this->crawler->start()->willThrow(new \RuntimeException('Aborted the crawl'));

        self::assertSame(Command::FAILURE, $this->runCommand());
    }

    private function runCommand(): int
    {
        // The application turns an exception into an exit code, like when the command runs from the console
        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new CrawlCommand($this->crawler->reveal()));

        return (new ApplicationTester($application))->run(['command' => 'setono:sylius-consent-management:crawl']);
    }
}
