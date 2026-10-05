<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Command;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSigner;
use Setono\SyliusConsentManagementPlugin\Command\SignConsentUrlCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;

final class SignConsentUrlCommandTest extends TestCase
{
    private ConsentOverrideSigner $signer;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->signer = new ConsentOverrideSigner('secret');
        $this->commandTester = new CommandTester(new SignConsentUrlCommand($this->signer));
    }

    /**
     * @test
     */
    public function it_prints_a_signed_url_that_grants_consent_for_all_categories_for_an_hour(): void
    {
        $this->commandTester->execute(['url' => 'https://shop.example.com/en_US/products/mug?gclid=abc']);

        $this->commandTester->assertCommandIsSuccessful();

        $request = $this->getSignedRequest();
        self::assertSame('/en_US/products/mug', $request->getPathInfo());
        self::assertSame('abc', $request->query->get('gclid'));
        self::assertSame('1', $request->query->get('_consent'));
        self::assertEqualsWithDelta(time() + 3600, (int) $request->query->get('_consent_expires'), 5);
    }

    /**
     * @test
     */
    public function it_grants_consent_for_the_given_categories(): void
    {
        $this->commandTester->execute([
            'url' => 'https://shop.example.com/',
            '--category' => ['marketing', 'statistical'],
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        self::assertSame(['marketing' => '1', 'statistical' => '1'], $this->getSignedRequest()->query->all('_consent'));
    }

    /**
     * @test
     */
    public function it_uses_the_given_expiry(): void
    {
        $this->commandTester->execute([
            'url' => 'https://shop.example.com/',
            '--expires' => '+10 minutes',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        self::assertEqualsWithDelta(time() + 600, (int) $this->getSignedRequest()->query->get('_consent_expires'), 5);
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidExpiries
     */
    public function it_rejects_invalid_expiries(string $expires): void
    {
        $this->commandTester->execute([
            'url' => 'https://shop.example.com/',
            '--expires' => $expires,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('invalid or not in the future', $this->commandTester->getDisplay());
        self::assertStringNotContainsString('_consent_signature', $this->commandTester->getDisplay());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidExpiries(): iterable
    {
        yield 'malformed' => ['in a while'];
        yield 'in the past' => ['-1 minute'];
    }

    private function getSignedRequest(): Request
    {
        $request = Request::create(trim($this->commandTester->getDisplay()));
        self::assertTrue($this->signer->isSigned($request));

        return $request;
    }
}
