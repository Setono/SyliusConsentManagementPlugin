<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Factory\CategoryFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategoryProvider implements CategoryProviderInterface
{
    use RecoversFromConcurrentCreationTrait;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryFactoryInterface $categoryFactory,
        private readonly RepositoryInterface $localeRepository,
        private readonly TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        if ([] !== $categories) {
            return $categories;
        }

        try {
            return $this->createDefaultCategories();
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request (typically right after installing the plugin) created the categories first
            return $this->recoverFromConcurrentCreation($e, $this->categoryRepository->getClassName(), function (): ?array {
                $categories = $this->categoryRepository->findAll();

                return [] === $categories ? null : $categories;
            });
        }
    }

    /**
     * The categories are flushed together, so a concurrent request never reads only some of them
     *
     * @return non-empty-list<CategoryInterface>
     */
    private function createDefaultCategories(): array
    {
        $manager = $this->getManager($this->categoryRepository->getClassName());
        $categories = [];

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
            $manager->persist($category);

            $categories[] = $category;
        }

        $manager->flush();

        return $categories;
    }
}
