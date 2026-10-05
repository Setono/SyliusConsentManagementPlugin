<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

final class CrawlResult
{
    public function __construct(
        public readonly int $crawled,
        public readonly int $failed,
    ) {
    }
}
