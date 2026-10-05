<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

interface CrawlerInterface
{
    /**
     * A URL that fails to load is counted in the result and doesn't stop the crawl. Anything else that goes wrong,
     * e.g. a browser that won't start or a browser session that is gone, aborts the crawl with an exception
     */
    public function start(): CrawlResult;
}
