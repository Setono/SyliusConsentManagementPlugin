<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

use Psr\Log\LoggerAwareInterface;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-consent-management:crawl',
    description: 'Will crawl a small subset of pages in your Sylius store to detect cookies',
)]
final class CrawlCommand extends Command
{
    public function __construct(private readonly CrawlerInterface $crawler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->crawler instanceof LoggerAwareInterface) {
            $this->crawler->setLogger(new ConsoleLogger($output));
        }

        // An aborted crawl throws, which also fails the command
        $result = $this->crawler->start();

        return 0 === $result->failed ? Command::SUCCESS : Command::FAILURE;
    }
}
