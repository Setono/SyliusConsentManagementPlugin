<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider\Sample;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\FirewallMapInterface;

final class SampleDecider implements SampleDeciderInterface
{
    public const SAMPLE_QUERY_PARAMETER = '_sample';

    public function __construct(
        private readonly FirewallMapInterface $firewallMap,
        private readonly array $firewalls,
        private readonly float $sampleRate,
    ) {
    }

    public function sample(Request $request, string $context): bool
    {
        if ($request->query->has(self::SAMPLE_QUERY_PARAMETER)) {
            if (in_array((string) $request->query->get(self::SAMPLE_QUERY_PARAMETER), ['false', '0', 'no', 'n', 'off'], true)) {
                return false;
            }

            return true;
        }

        $sampleRateResult = random_int(1, mt_getrandmax()) / mt_getrandmax() <= $this->sampleRate;

        if (!$sampleRateResult) {
            return false;
        }

        if (SampleDeciderInterface::CONTEXT_CLIENT_SIDE === $context) {
            $userAgent = $request->headers->get('User-Agent');
            if (!is_string($userAgent) || !str_contains($userAgent, 'Chrome')) {
                return false;
            }
        }

        if (!$this->firewallMap instanceof FirewallMap) {
            return true;
        }

        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        if (null === $firewallConfig) {
            return true;
        }

        return in_array($firewallConfig->getName(), $this->firewalls, true);
    }
}
