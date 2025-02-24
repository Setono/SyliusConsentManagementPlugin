<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

use Symfony\Component\HttpFoundation\Request;

interface SampleDeciderInterface
{
    public const CONTEXT_SERVER_SIDE = 'server_side';

    public const CONTEXT_CLIENT_SIDE = 'client_side';

    /**
     * @param self::CONTEXT_* $context
     */
    public function sample(Request $request, string $context): bool;
}
