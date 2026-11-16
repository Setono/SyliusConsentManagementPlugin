<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * @test
     */
    public function it_limits_sampling_by_default(): void
    {
        $this->assertProcessedConfigurationEquals([], [
            'sampling' => [
                'rate_limit' => [
                    'enabled' => true,
                    'recordings' => ['limit' => 10, 'interval' => '1 hour'],
                    'new_cookies' => ['limit' => 10, 'interval' => '1 day'],
                ],
            ],
        ], 'sampling.rate_limit');
    }

    /**
     * @test
     */
    public function it_accepts_other_sampling_rate_limits(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['sampling' => ['rate_limit' => ['recordings' => ['limit' => 100, 'interval' => '30 minutes']]]],
        ], [
            'sampling' => [
                'rate_limit' => [
                    'recordings' => ['limit' => 100, 'interval' => '30 minutes'],
                    'enabled' => true,
                    'new_cookies' => ['limit' => 10, 'interval' => '1 day'],
                ],
            ],
        ], 'sampling.rate_limit');
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidSamplingRateLimits
     */
    public function it_rejects_invalid_sampling_rate_limits(array $rateLimit): void
    {
        $this->assertConfigurationIsInvalid([['sampling' => ['rate_limit' => $rateLimit]]]);
    }

    /**
     * @return iterable<string, array{array<string, array<string, mixed>>}>
     */
    public static function provideInvalidSamplingRateLimits(): iterable
    {
        yield 'zero limit' => [['recordings' => ['limit' => 0]]];
        yield 'limit that is not an integer' => [['new_cookies' => ['limit' => '10']]];
        yield 'interval that is not a relative time' => [['new_cookies' => ['interval' => 'soon']]];
        yield 'zero interval' => [['recordings' => ['interval' => '0 seconds']]];
        yield 'negative interval' => [['recordings' => ['interval' => '-1 hour']]];
    }
}
