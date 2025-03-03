<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Event;

final class ConsentUpdated
{
    public function __construct(
        /** @var list<string> $consentedCategories */
        public readonly array $consentedCategories,
    ) {
    }
}
