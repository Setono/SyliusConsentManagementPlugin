<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Component\Resource\Factory\TranslatableFactoryInterface;

interface CategoryFactoryInterface extends TranslatableFactoryInterface
{
    public function createNew(): CategoryInterface;

    /**
     * @param array<string, array{name: string, description: string}> $translations
     */
    public function createWithData(string $code, array $translations, bool $necessary = false): CategoryInterface;
}
