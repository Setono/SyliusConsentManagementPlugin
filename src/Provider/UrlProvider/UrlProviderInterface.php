<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

interface UrlProviderInterface
{
    /**
     * @return iterable<Url>
     */
    public function getUrls(): iterable;
}
