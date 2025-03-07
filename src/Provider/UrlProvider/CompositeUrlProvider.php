<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Setono\CompositeCompilerPass\CompositeService;

/**
 * @extends CompositeService<UrlProviderInterface>
 */
final class CompositeUrlProvider extends CompositeService implements UrlProviderInterface
{
    public function getUrls(): iterable
    {
        foreach ($this->services as $service) {
            yield from $service->getUrls();
        }
    }
}
