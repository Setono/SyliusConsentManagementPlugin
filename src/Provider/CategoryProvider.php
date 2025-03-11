<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Factory\CategoryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategoryProvider implements CategoryProviderInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryFactoryInterface $categoryFactory,
        private readonly RepositoryInterface $localeRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        if ([] !== $categories) {
            return $categories;
        }

        $defaultConsents = array_merge(DefaultConsents::all(), ['necessary']);

        foreach ($defaultConsents as $defaultConsent) {
            $translations = [];

            /** @var LocaleInterface $locale */
            foreach ($this->localeRepository->findAll() as $locale) {
                $localeCode = (string) $locale->getCode();

                $translations[$localeCode] = [
                    'name' => $this->translator->trans(
                        sprintf('setono_sylius_consent_management.ui.default_categories.%s.name', $defaultConsent),
                        [],
                        null,
                        $localeCode,
                    ),
                    'description' => $this->translator->trans(
                        sprintf('setono_sylius_consent_management.ui.default_categories.%s.description', $defaultConsent),
                        [],
                        null,
                        $localeCode,
                    ),
                ];
            }

            $category = $this->categoryFactory->createWithData($defaultConsent, $translations, 'necessary' === $defaultConsent);
            $this->categoryRepository->add($category);

            $categories[] = $category;
        }

        return $categories;
    }
}
