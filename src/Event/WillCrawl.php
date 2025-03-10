<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Url;
use Symfony\Component\Panther\Client;

final class WillCrawl
{
    public function __construct(
        public readonly CrawlerInterface $crawler,
        public readonly Client $client,
        public readonly Url $url,
    ) {
    }
}
