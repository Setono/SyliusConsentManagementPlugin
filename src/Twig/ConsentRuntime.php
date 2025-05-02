<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\Consent\ConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\SyliusConsentManagementPlugin\Provider\ConsentedCategoriesProviderInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ConsentRuntime implements RuntimeExtensionInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ConsentCheckerInterface $consentChecker,
        private readonly ConsentedCategoriesProviderInterface $consentedCategoriesProvider,
    ) {
        $this->logger = new NullLogger();
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

    /**
     * Returns a JSON string like so ["necessary", "functional"]
     */
    public function consentedCategoriesJson(): string
    {
        try {
            return json_encode($this->consentedCategoriesProvider->getCategories(), \JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to encode consented categories', ['exception' => $e]);
        }

        return '[]';
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
