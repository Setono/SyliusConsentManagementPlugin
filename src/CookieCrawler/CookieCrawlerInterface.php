<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\CookieCrawler;

interface CookieCrawlerInterface
{
    public const STRATEGY_BREADTH_FIRST = 'bf';

    public const STRATEGY_DEPTH_FIRST = 'df';

    public const STRATEGY_RANDOM = 'random';

    /**
     * @param int $maxPages the maximum number of pages to crawl
     *
     * @return array<array-key, CrawledCookie>
     */
    public function crawl(string $url, int $maxPages = 10, string $strategy = self::STRATEGY_RANDOM): array;
}
