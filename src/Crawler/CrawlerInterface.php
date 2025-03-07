<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

interface CrawlerInterface
{
    public function start(): void;
}
