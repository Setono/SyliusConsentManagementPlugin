<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Sampling;

/**
 * Short-lived tokens that allow a page, which was chosen for client-side sampling, to post its cookies within a limited
 * time. Without them, anybody could post arbitrary cookie names to the sample endpoint.
 *
 * A token is meant to be used once, but that's best-effort, see the implementation
 */
interface SampleTokenManagerInterface
{
    public const QUERY_PARAMETER = '_sample_token';

    public function create(): string;

    /**
     * Returns true if the token is valid and hasn't been used before, and marks it as used. Parallel calls with the same
     * token may all return true
     */
    public function consume(string $token): bool;
}
