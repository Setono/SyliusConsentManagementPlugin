<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CategoryTranslationInterface;
use Sylius\Resource\Factory\FactoryInterface;

interface CategoryTranslationFactoryInterface extends FactoryInterface
{
    public function createNew(): CategoryTranslationInterface;

    /**
     * @param array{name: string, description: string} $data
     */
    public function createFromData(string $localeCode, array $data): CategoryTranslationInterface;
}
