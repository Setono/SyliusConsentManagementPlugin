<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Grid;

use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Component\Grid\Definition\Action;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddCreateDefaultCategoriesButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.grid.setono_consent_management_admin_category' => 'add',
        ];
    }

    public function add(GridDefinitionConverterEvent $event): void
    {
        if ($this->categoryRepository->hasOne()) {
            return;
        }

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
