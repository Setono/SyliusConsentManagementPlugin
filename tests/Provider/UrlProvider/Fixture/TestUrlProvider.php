<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\Fixture;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\AbstractUrlProvider;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Exposes AbstractUrlProvider::generateUrl() for testing
 */
final class TestUrlProvider extends AbstractUrlProvider
{
    public function __construct(ManagerRegistry $managerRegistry, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($managerRegistry, $urlGenerator, ChannelInterface::class);
    }

    public function getUrls(): iterable
    {
        return [];
    }

    public function generate(ChannelInterface $channel, string $locale, string $route, array $parameters = []): string
    {
        return $this->generateUrl($channel, $locale, $route, $parameters);
    }
}
