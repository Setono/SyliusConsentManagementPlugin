<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusConsentManagementPlugin\Model\CategoryTranslationInterface;

/**
 * This listener will shorten any unique constraint key that is longer than 64 characters.
 *
 * @see https://dev.mysql.com/doc/refman/8.4/en/identifier-length.html
 * @see \Sylius\Bundle\ResourceBundle\EventListener\ORMTranslatableListener
 */
final class ORMTranslatableListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $reflection = $classMetadata->getReflectionClass();

        if ($reflection->isAbstract() || !$reflection->implementsInterface(CategoryTranslationInterface::class)) {
            return;
        }

        /** @var array<string, array> $uniqueConstraints */
        $uniqueConstraints = $classMetadata->table['uniqueConstraints'] ?? [];
        if ([] === $uniqueConstraints) {
            throw new \LogicException(sprintf('No unique constraints found for %s', $classMetadata->getName()));
        }

        foreach ($uniqueConstraints as $uniqueConstraintKey => $uniqueConstraint) {
            if (strlen($uniqueConstraintKey) <= 64) {
                continue;
            }

            $newUniqueConstraintKey = substr($uniqueConstraintKey, 0, 64);
            $classMetadata->table['uniqueConstraints'][$newUniqueConstraintKey] = $uniqueConstraint;
            unset($classMetadata->table['uniqueConstraints'][$uniqueConstraintKey]);
        }
    }
}
