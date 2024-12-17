<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CategoryTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class CategoryTranslationFactory implements CategoryTranslationFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decorated)
    {
    }

    public function createNew(): CategoryTranslationInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, CategoryTranslationInterface::class);

        return $obj;
    }

    public function createFromData(string $localeCode, array $data): CategoryTranslationInterface
    {
        $obj = $this->createNew();
        $obj->setLocale($localeCode);
        $obj->setName($data['name']);
        $obj->setDescription($data['description']);

        return $obj;
    }
}
