<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class ServiceRepository extends EntityRepository implements ServiceRepositoryInterface
{
    public function findAllIndexedByCategory(): array
    {
        $result = [
            ServiceInterface::CATEGORY_MARKETING => [],
            ServiceInterface::CATEGORY_PREFERENCES => [],
            ServiceInterface::CATEGORY_STATISTICS => [],
        ];

        $services = $this->findAll();

        foreach ($services as $service) {
            Assert::isInstanceOf($service, ServiceInterface::class);

            $result[(string) $service->getCategory()][] = $service;
        }

        return $result;
    }

    public function findEnabledIndexedByCategory(ChannelInterface $channel): array
    {
        $result = [
            ServiceInterface::CATEGORY_MARKETING => [],
            ServiceInterface::CATEGORY_PREFERENCES => [],
            ServiceInterface::CATEGORY_STATISTICS => [],
        ];

        $services = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = true')
            ->andWhere(':channel MEMBER OF o.channels')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getResult()
        ;

        foreach ($services as $service) {
            Assert::isInstanceOf($service, ServiceInterface::class);

            $result[(string) $service->getCategory()][] = $service;
        }

        return $result;
    }
}
