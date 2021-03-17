<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ServiceRepository extends EntityRepository implements ServiceRepositoryInterface
{
    public function findAllIndexedByCategory(): array
    {
        $result = [
            'preferences' => [],
            'statistics' => [],
            'marketing' => [],
        ];

        /** @var array<array-key, ServiceInterface> $services */
        $services = $this->findAll();

        foreach ($services as $service) {
            $result[(string) $service->getCategory()][] = $service;
        }

        return $result;
    }
}
