<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Setono\SyliusConsentManagementPlugin\Model\ServiceInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ServiceRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class ServiceContext implements Context
{
    private ServiceRepositoryInterface $serviceRepository;

    private FactoryInterface $serviceFactory;

    public function __construct(ServiceRepositoryInterface $serviceRepository, FactoryInterface $serviceFactory)
    {
        $this->serviceRepository = $serviceRepository;
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @Given the store uses multiple services, at least one in each category
     */
    public function storeUsesMultipleServices(): void
    {
        foreach (self::services() as $data) {
            $obj = $this->createService($data[0], $data[1]);
            $this->saveService($obj);
        }
    }

    private function createService(string $index, string $category): ServiceInterface
    {
        /** @var ServiceInterface $service */
        $service = $this->serviceFactory->createNew();

        $service->setCode(sprintf('service_%s', $index));
        $service->setName(sprintf('Service %s', $index));
        $service->setDescription('Lipsum');
        $service->setCategory($category);

        return $service;
    }

    private function saveService(ServiceInterface $service): void
    {
        $this->serviceRepository->add($service);
    }

    /**
     * @return iterable<array<string>>
     */
    private static function services(): iterable
    {
        $i = 0;

        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_PREFERENCES, 'Very important service'];

        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_STATISTICS, 'Very important service'];

        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
        yield [(string) ++$i, ServiceInterface::CATEGORY_MARKETING, 'Very important service'];
    }
}
