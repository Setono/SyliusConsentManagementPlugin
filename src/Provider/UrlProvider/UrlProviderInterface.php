<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

interface UrlProviderInterface
{
    /**
     * @return iterable<string>
     */
    public function getUrls(): iterable;
}
