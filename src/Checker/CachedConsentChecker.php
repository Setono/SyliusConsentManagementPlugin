<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\Consent\ConsentCheckerInterface;

/**
 * This class will cache the consent for the request life cycle directly in memory
 */
final class CachedConsentChecker implements ConsentCheckerInterface
{
    /** @var array<string, bool> */
    private array $consents = [];

    public function __construct(private readonly ConsentCheckerInterface $decorated)
    {
    }

    public function isGranted(string $consent): bool
    {
        if (!array_key_exists($consent, $this->consents)) {
            $this->consents[$consent] = $this->decorated->isGranted($consent);
        }

        return $this->consents[$consent];
    }
}
