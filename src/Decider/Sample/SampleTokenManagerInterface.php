<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

/**
 * Single-use tokens that allow a page, which was chosen for client-side sampling, to post its cookies once within a
 * limited time. Without them, anybody could post arbitrary cookie names to the sample endpoint
 */
interface SampleTokenManagerInterface
{
    public const QUERY_PARAMETER = '_sample_token';

    public function create(): string;

    /**
     * Returns true if the token is valid and hasn't been used before, and marks it as used
     */
    public function consume(string $token): bool;
}
