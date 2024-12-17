<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Grid;

use Sylius\Component\Grid\Definition\Action;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// todo maybe not show this when default categories are already created
final class AddCreateDefaultCategoriesButtonSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.grid.setono_consent_management_admin_category' => 'add',
        ];
    }

    public function add(GridDefinitionConverterEvent $event): void
    {
        $action = Action::fromNameAndType('create_default_categories', 'default');
        $action->setLabel('setono_sylius_consent_management.ui.create_default_categories');
        $action->setIcon('plus');
        $action->setOptions([
            'link' => [
                'route' => 'setono_sylius_consent_management_admin_create_default_categories',
            ],
        ]);

        $event->getGrid()->getActionGroup('main')->addAction($action);
    }
}
