<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<CategoryInterface>
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function findAll(): array;

    public function findOneByCode(string $code): ?CategoryInterface;

    /**
     * @return list<CategoryInterface>
     */
    public function findNecessary(): array;
}
