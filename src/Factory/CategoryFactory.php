<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class CategoryFactory implements CategoryFactoryInterface
{
    public function __construct(
        private readonly FactoryInterface $decorated,
        private readonly CategoryTranslationFactoryInterface $categoryTranslationFactory,
    ) {
    }

    public function createNew(): CategoryInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, CategoryInterface::class);

        return $obj;
    }

    public function createWithData(string $code, array $translations, bool $necessary = false): CategoryInterface
    {
        $obj = $this->createNew();
        $obj->setCode($code);
        $obj->setNecessary($necessary);

        foreach ($translations as $localeCode => $translationData) {
            $obj->addTranslation($this->categoryTranslationFactory->createFromData($localeCode, $translationData));
        }

        return $obj;
    }
}
