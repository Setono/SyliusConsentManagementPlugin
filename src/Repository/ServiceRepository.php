<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ServiceRepository extends EntityRepository implements ServiceRepositoryInterface
{
    public function findAllIndexedByCategory(): array
    {
        $result = [
            'marketing' => [],
            'preferences' => [],
            'statistics' => [],
        ];

        $services = $this->findAll();

        foreach ($services as $service) {
            Assert::isInstanceOf($service, ServiceInterface::class);

            $result[(string) $service->getCategory()][] = $service;
        }

        return $result;
    }
}
