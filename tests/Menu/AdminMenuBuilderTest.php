<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Menu;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Menu\AdminMenuBuilder;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuBuilderTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_menu_item(): void
    {
        $factory = new MenuFactory();
        $item = new MenuItem('test', $factory);

        $event = new MenuBuilderEvent($factory, $item);

        $builder = new AdminMenuBuilder();
        $builder->addSection($event);

        $child = $item->getChild('consent_management');

        self::assertNotNull($child);
        self::assertNotNull($child->getChild('consent_entries'));
        self::assertNotNull($child->getChild('services'));
    }

    /**
     * @test
     */
    public function it_uses_existing_header(): void
    {
        $factory = new MenuFactory();
        $item = new MenuItem('test', $factory);
        $item->addChild('consent_management');

        $event = new MenuBuilderEvent($factory, $item);

        $builder = new AdminMenuBuilder();
        $builder->addSection($event);

        $child = $item->getChild('consent_management');

        self::assertNotNull($child);
        self::assertSame('consent_management', $child->getLabel()); // this means the header was not added in the listener (because we set another label in the listener)
    }
}
