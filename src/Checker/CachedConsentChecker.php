<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Setono\Consent\ConsentCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * This class will cache the consent for the request life cycle directly in memory.
 * The cache is reset between requests (kernel.reset), which matters when the kernel handles several
 * requests, e.g. with FrankenPHP's worker mode or RoadRunner
 */
final class CachedConsentChecker implements ConsentCheckerInterface, ResetInterface
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

    public function reset(): void
    {
        $this->consents = [];
    }
}
