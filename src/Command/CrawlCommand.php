<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

use GuzzleHttp\Psr7\Uri;
use Setono\SyliusConsentManagementPlugin\Crawler\Crawler;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Panther\Client;

final class CrawlCommand extends Command
{
    protected static $defaultName = 'setono:sylius-consent-management:crawl';

    protected static $defaultDescription = 'This command crawls your store to pick up ';

    private ChannelRepositoryInterface $channelRepository;

    private int $pagesToCrawl;

    public function __construct(ChannelRepositoryInterface $channelRepository, int $pagesToCrawl)
    {
        parent::__construct();

        $this->channelRepository = $channelRepository;
        $this->pagesToCrawl = $pagesToCrawl;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Crawling %d pages on each of your channels looking for cookies 🍪🍪🍪', $this->pagesToCrawl));

        $crawler = new Crawler(new Uri('https://www.2trendy.dk'), 5);
        $crawler->start();

        return Command::SUCCESS;
        $client = Client::createChromeClient();

        $client->request('GET', 'https://www.2trendy.dk');
        dump($client->getCookieJar()->all());

        $filename = getcwd() . '/screen.png';

        $client->takeScreenshot($filename);
        $io->writeln(sprintf('Screenshot saved to %s', $filename));

        return Command::SUCCESS;
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        foreach ($channels as $channel) {
            $hostname = $channel->getHostname();
            if (null === $hostname) {
                $io->error(sprintf('No hostname set on channel "%s"', (string) $channel->getCode()));

                continue;
            }
        }

        return Command::SUCCESS;
    }
}
