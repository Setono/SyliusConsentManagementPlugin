<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Crawler\Fixture;

use Psr\Log\AbstractLogger;

/**
 * Keeps the logged messages with their placeholders replaced, prefixed with the level
 */
final class TestLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_string($value) || is_int($value)) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        $this->logs[] = sprintf('%s: %s', is_string($level) ? $level : 'unknown', strtr((string) $message, $replacements));
    }

    public function lastLog(): ?string
    {
        return [] === $this->logs ? null : $this->logs[array_key_last($this->logs)];
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return array_values(array_filter($this->logs, static fn (string $log): bool => str_starts_with($log, 'error: ')));
    }
}
