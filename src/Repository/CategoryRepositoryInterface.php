<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function findAll(): array;

    public function findOneByCode(string $code): ?CategoryInterface;
}
