<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;

interface CategoryProviderInterface
{
    /**
     * @return array<array-key, CategoryInterface>
     */
    public function getCategories(): array;
}
