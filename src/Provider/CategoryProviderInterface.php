<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;

interface CategoryProviderInterface
{
    /**
     * @return non-empty-array<array-key, CategoryInterface>
     */
    public function getCategories(): array;
}
