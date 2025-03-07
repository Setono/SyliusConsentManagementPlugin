<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

use League\Uri\Contracts\UriInterface;
use League\Uri\Uri;
use Setono\SyliusConsentManagementPlugin\Crawler\CrawlerInterface;
use Symfony\Component\Panther\Client;

final class WillCrawl
{
    public UriInterface $url;

    public function __construct(
        public readonly CrawlerInterface $crawler,
        public readonly Client $client,
        UriInterface|string $url,
    ) {
        if (is_string($url)) {
            $url = Uri::createFromString($url);
        }

        $this->url = $url;
    }
}
