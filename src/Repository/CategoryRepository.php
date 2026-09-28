<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class CategoryRepository extends EntityRepository implements CategoryRepositoryInterface
{
    /**
     * @return list<CategoryInterface>
     */
    public function findAll(): array
    {
        /** @var list<CategoryInterface> $categories */
        $categories = $this->findBy([], ['position' => 'ASC']);

        return $categories;
    }
}
