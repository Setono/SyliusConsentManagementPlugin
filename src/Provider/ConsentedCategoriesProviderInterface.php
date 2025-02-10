<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

interface ConsentedCategoriesProviderInterface
{
    /**
     * @psalm-return ($onlyGranted is true ? array<string, bool> : list<string>)
     */
    public function getCategories(bool $onlyGranted = true): array;
}
