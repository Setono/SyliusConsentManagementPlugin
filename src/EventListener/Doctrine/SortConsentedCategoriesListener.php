<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;

/**
 * By sorting the consented categories, it makes it easier to query for statistics
 */
final class SortConsentedCategoriesListener
{
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        self::sortConsentedCategories($eventArgs);
    }

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        self::sortConsentedCategories($eventArgs);
    }

    private static function sortConsentedCategories(PrePersistEventArgs|PreUpdateEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ConsentEntryInterface) {
            return;
        }

        $consentedCategories = $obj->getConsentedCategories();
        if ([] === $consentedCategories) {
            return;
        }

        sort($consentedCategories, \SORT_STRING);

        $obj->setConsentedCategories($consentedCategories);
    }
}
