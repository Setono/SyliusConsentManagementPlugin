<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

interface ConsentedCategoriesProviderInterface
{
    /**
     * @return ($onlyGranted is true ? list<string> : array<string, bool>)
     */
    public function getCategories(bool $onlyGranted = true): array;
}
