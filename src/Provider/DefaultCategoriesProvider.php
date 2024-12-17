<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\SyliusConsentManagementPlugin\Factory\CategoryFactoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DefaultCategoriesProvider implements DefaultCategoriesProviderInterface
{
    public function __construct(
        private readonly CategoryFactoryInterface $categoryFactory,
        private readonly RepositoryInterface $localeRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getCategories(): \Generator
    {
        // todo get from constants
        $defaultCategories = ['marketing', 'preferences', 'statistics', 'necessary'];

        foreach ($defaultCategories as $defaultCategory) {
            $translations = [];

            /** @var LocaleInterface $locale */
            foreach ($this->localeRepository->findAll() as $locale) {
                $localeCode = (string) $locale->getCode();

                $translations[$localeCode] = [
                    'name' => $this->translator->trans(
                        id: sprintf('setono_sylius_consent_management.ui.default_categories.%s.name', $defaultCategory),
                        locale: $localeCode,
                    ),
                    'description' => $this->translator->trans(
                        id: sprintf('setono_sylius_consent_management.ui.default_categories.%s.description', $defaultCategory),
                        locale: $localeCode,
                    ),
                ];
            }

            yield $this->categoryFactory->createWithData($defaultCategory, $translations, 'necessary' === $defaultCategory);
        }
    }
}
