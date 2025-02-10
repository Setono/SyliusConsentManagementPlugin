<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

interface ConsentedCategoriesProviderInterface
{
    /**
     * @psalm-return ($includeNonConsented is true ? array<string, bool> : list<string>)
     */
    public function getCategories(bool $includeNonConsented = false): array;
}
