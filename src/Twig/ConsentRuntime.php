<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ConsentRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ConsentCheckerInterface $consentChecker,
        private readonly RepositoryInterface $categoryRepository,
    ) {
    }

    public function isGranted(string $consent): bool
    {
        return $this->consentChecker->isGranted($consent);
    }

    public function functionalGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_FUNCTIONAL);
    }

    public function marketingGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_MARKETING);
    }

    public function statisticalGranted(): bool
    {
        return $this->consentChecker->isGranted(DefaultConsents::CONSENT_STATISTICAL);
    }

    public function scriptTag(string $src, string ...$consents): string
    {
        foreach ($consents as $consent) {
            if (!$this->consentChecker->isGranted($consent)) {
                return sprintf('<script type="text/plain" data-sscm-consent="%s" data-sscm-src="%s"></script>', implode(',', $consents), $src);
            }
        }

        return sprintf('<script src="%s"></script>', $src);
    }

    public function scriptTagAttributes(string ...$consents): string
    {
        foreach ($consents as $consent) {
            if (!$this->consentChecker->isGranted($consent)) {
                return sprintf(' type="text/plain" data-sscm-consent="%s"', implode(',', $consents));
            }
        }

        return '';
    }

    public function consentedCategoriesScriptTag(): string
    {
        $categories = [];

        /** @var CategoryInterface $category */
        foreach ($this->categoryRepository->findAll() as $category) {
            if (!$this->consentChecker->isGranted((string) $category->getCode())) {
                continue;
            }

            $categories[] = $category->getCode();
        }

        return sprintf('<script type="application/json" id="sscm-consented-categories-json">%s</script>', json_encode($categories, \JSON_THROW_ON_ERROR));
    }
}
