<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Setono\Consent\ConsentCheckerInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;

final class ConsentedCategoriesProvider implements ConsentedCategoriesProviderInterface
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository, private readonly ConsentCheckerInterface $consentChecker)
    {
    }

    public function getCategories(bool $includeNonConsented = false): array
    {
        /** @var array<string, bool> $categories */
        $categories = [];

        foreach ($this->categoryRepository->findAll() as $category) {
            $code = (string) $category->getCode();
            $categories[$code] = $this->consentChecker->isGranted($code);
        }

        if ($includeNonConsented) {
            return $categories;
        }

        return array_keys(array_filter($categories));
    }
}
