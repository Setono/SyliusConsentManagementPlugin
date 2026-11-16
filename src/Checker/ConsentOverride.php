<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Checker;

use Symfony\Component\HttpFoundation\Request;

/**
 * A consent override in the query string, e.g. ?_consent=1 or ?_consent[marketing]=0, and its signature if it's
 * signed (see ConsentOverrideSignerInterface)
 */
final class ConsentOverride
{
    public const QUERY_PARAM = '_consent';

    public const EXPIRES_QUERY_PARAM = '_consent_expires';

    public const SIGNATURE_QUERY_PARAM = '_consent_signature';

    public function __construct(
        /** The value of the _consent query parameter, e.g. '1' or ['marketing' => '0'] */
        public readonly string|array $consent,
        public readonly ?int $expires = null,
        public readonly ?string $signature = null,
    ) {
    }

    /**
     * Returns null if the request doesn't override consent
     */
    public static function fromRequest(Request $request): ?self
    {
        $query = $request->query->all();

        $consent = $query[self::QUERY_PARAM] ?? null;
        if (!is_string($consent) && !is_array($consent)) {
            return null;
        }

        $expires = $query[self::EXPIRES_QUERY_PARAM] ?? null;
        $signature = $query[self::SIGNATURE_QUERY_PARAM] ?? null;

        return new self(
            $consent,
            is_string($expires) && 1 === preg_match('/^\d+$/', $expires) ? (int) $expires : null,
            is_string($signature) ? $signature : null,
        );
    }

    /**
     * Returns true if the override grants consent for the category, false if it denies it, and null if it doesn't cover it
     */
    public function grants(string $category): ?bool
    {
        $value = is_array($this->consent) ? $this->consent[$category] ?? null : $this->consent;

        return is_numeric($value) ? 1 === (int) $value : null;
    }
}
