<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Symfony\Component\Panther\Client;

final class CrawlStarted
{
    public function __construct(
        public readonly CrawlerInterface $crawler,
        public readonly Client $client,
    ) {
    }
}
