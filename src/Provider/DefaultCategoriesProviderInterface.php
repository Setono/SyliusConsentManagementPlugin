<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;

interface DefaultCategoriesProviderInterface
{
    /**
     * @return iterable<CategoryInterface>
     */
    public function getCategories(): iterable;
}
