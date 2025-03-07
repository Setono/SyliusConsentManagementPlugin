<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Decider\Sample;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDecider;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDeciderInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\FirewallMapInterface;

final class SampleDeciderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<FirewallMap> */
    private ObjectProphecy $firewallMap;

    protected function setUp(): void
    {
        $this->firewallMap = $this->prophesize(FirewallMap::class);
    }

    /**
     * @test
     *
     * @dataProvider provideFalsySampleParameters
     */
    public function it_does_not_sample_if_sample_parameter_is_falsy(string $sampleParameter): void
    {
        self::assertFalse($this->createDecider(0)->sample(Request::create('/?_sample=' . $sampleParameter), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    /**
     * @test
     *
     * @dataProvider provideTruthySampleParameters
     */
    public function it_samples_if_sample_parameter_is_truthy(string $sampleParameter): void
    {
        self::assertTrue($this->createDecider(0)->sample(Request::create('/?_sample=' . $sampleParameter), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    /**
     * @test
     */
    public function it_does_not_sample(): void
    {
        self::assertFalse($this->createDecider(0)->sample(Request::create('/'), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_context_is_client_side_and_user_agent_is_not_eligible(): void
    {
        self::assertFalse($this->createDecider(1)->sample(Request::create(uri: '/', server: ['HTTP_USER_AGENT' => 'Mozilla']), SampleDeciderInterface::CONTEXT_CLIENT_SIDE));
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_is_not_expected_type(): void
    {
        self::assertTrue(
            $this->createDecider(1, $this->prophesize(FirewallMapInterface::class)->reveal())
                ->sample(Request::create('/'), SampleDeciderInterface::CONTEXT_SERVER_SIDE),
        );
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_config_is_null(): void
    {
        self::assertTrue($this->createDecider(1)->sample(Request::create('/'), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    /**
     * @test
     */
    public function it_samples_if_firewall_config_is_eligible(): void
    {
        $this->firewallMap->getFirewallConfig(Argument::type(Request::class))->willReturn(new FirewallConfig('shop', 'user_checker'));

        self::assertTrue($this->createDecider(1)->sample(Request::create('/'), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    /**
     * @test
     */
    public function it_does_not_sample_if_firewall_config_is_not_eligible(): void
    {
        $this->firewallMap->getFirewallConfig(Argument::type(Request::class))->willReturn(new FirewallConfig('admin', 'user_checker'));

        self::assertFalse($this->createDecider(1)->sample(Request::create('/'), SampleDeciderInterface::CONTEXT_SERVER_SIDE));
    }

    private function createDecider(float $sampleRate, FirewallMapInterface $firewallMap = null): SampleDecider
    {
        $firewallMap ??= $this->firewallMap->reveal();

        return new SampleDecider($firewallMap, ['shop'], $sampleRate);
    }

    /**
     * @return \Generator<array-key, array<array-key, string>>
     */
    private static function provideFalsySampleParameters(): \Generator
    {
        yield ['0'];
        yield ['false'];
        yield ['off'];
        yield ['no'];
        yield ['n'];
    }

    /**
     * @return \Generator<array-key, array<array-key, string>>
     */
    private static function provideTruthySampleParameters(): \Generator
    {
        yield ['1'];
        yield ['true'];
        yield ['on'];
        yield ['yes'];
        yield ['y'];
        yield [''];
    }
}
