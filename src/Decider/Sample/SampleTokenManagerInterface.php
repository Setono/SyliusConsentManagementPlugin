<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

/**
 * Tokens that allow a page, which was chosen for client-side sampling, to post its cookies for a limited time.
 * Without them, anybody could post arbitrary cookie names to the sample endpoint
 */
interface SampleTokenManagerInterface
{
    public const QUERY_PARAMETER = '_sample_token';

    public function create(): string;

    public function isValid(string $token): bool;
}
