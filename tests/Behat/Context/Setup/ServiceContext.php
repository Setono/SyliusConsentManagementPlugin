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
     * @Given the store uses a service named :name
     */
    public function storeUsesAService(string $name): void
    {
        $redirect = $this->createService($name);

        $this->saveRedirect($redirect);
    }

    private function createService(string $name): ServiceInterface
    {
        /** @var ServiceInterface $service */
        $service = $this->serviceFactory->createNew();

        $service->setName($name);
        $service->setDescription('Lipsum');
        $service->setCategory(ServiceInterface::CATEGORY_PREFERENCES);

        return $service;
    }

    private function saveRedirect(ServiceInterface $service): void
    {
        $this->serviceRepository->add($service);
    }
}
