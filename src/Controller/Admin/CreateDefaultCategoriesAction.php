<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\DefaultCategoriesProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final class CreateDefaultCategoriesAction
{
    use ORMTrait;

    public function __construct(
        private readonly DefaultCategoriesProviderInterface $defaultCategoriesProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(): RedirectResponse
    {
        $manager = null;

        foreach ($this->defaultCategoriesProvider->getCategories() as $category) {
            if ($this->hasCategory($category)) {
                continue;
            }

            $manager = $this->getManager($category);
            $manager->persist($category);
        }

        $manager?->flush();

        return new RedirectResponse($this->urlGenerator->generate('setono_sylius_consent_management_admin_category_index'));
    }

    private function hasCategory(CategoryInterface $category): bool
    {
        $code = $category->getCode();
        Assert::notNull($code);

        return null !== $this->getRepository($category)->findOneBy(['code' => $code]);
    }
}
