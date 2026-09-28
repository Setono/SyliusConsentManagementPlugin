<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Boots the whole application to test the routing configuration, so it shouldn't count towards code coverage
 *
 * @coversNothing
 */
final class AdminRoutesTest extends KernelTestCase
{
    private const ADMIN_PATH_NAME = 'backoffice';

    protected function setUp(): void
    {
        $_SERVER['SYLIUS_ADMIN_ROUTING_PATH_NAME'] = $_ENV['SYLIUS_ADMIN_ROUTING_PATH_NAME'] = self::ADMIN_PATH_NAME;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['SYLIUS_ADMIN_ROUTING_PATH_NAME'], $_ENV['SYLIUS_ADMIN_ROUTING_PATH_NAME']);
    }

    /**
     * @test
     */
    public function it_mounts_the_admin_routes_under_the_configured_sylius_admin_path(): void
    {
        // A dedicated environment gets its own cache directory, so the router is compiled with the custom admin path
        $kernel = self::bootKernel(['environment' => 'test_admin_path', 'debug' => false]);

        $router = $kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $adminRoutes = 0;
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            if (!str_starts_with($name, 'setono_sylius_consent_management_admin_')) {
                continue;
            }

            ++$adminRoutes;
            self::assertStringStartsWith(sprintf('/%s/consent-management/', self::ADMIN_PATH_NAME), $route->getPath(), sprintf('Route "%s"', $name));
        }

        self::assertGreaterThan(0, $adminRoutes);
    }
}
