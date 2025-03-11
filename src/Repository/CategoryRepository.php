<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class CategoryRepository extends EntityRepository implements CategoryRepositoryInterface
{
    public function findAll(): array
    {
        return $this->findBy([], ['position' => 'ASC']);
    }
}
