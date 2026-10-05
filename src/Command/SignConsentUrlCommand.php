<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

use League\Uri\Uri;
use League\Uri\UriModifier;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSignerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

/**
 * Granting consent through the query string (?_consent=1) only works when the override is signed (outside debug mode).
 * This command prints a signed URL, e.g. for QA or tag audits in production
 */
#[AsCommand(
    name: 'setono:sylius-consent-management:sign-consent-url',
    description: 'Will print a URL that grants consent through a signed _consent query parameter',
)]
final class SignConsentUrlCommand extends Command
{
    public function __construct(private readonly ConsentOverrideSignerInterface $consentOverrideSigner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'The URL to sign, e.g. https://example.com/en_US/')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The code of a category to grant consent for. Grants consent for all categories if omitted')
            ->addOption('expires', null, InputOption::VALUE_REQUIRED, 'When the URL stops granting consent, e.g. "+30 minutes" or "2030-01-01 12:00"', '+1 hour')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getArgument('url');
        Assert::string($url);

        $categories = $input->getOption('category');
        Assert::allString($categories);

        $expires = $input->getOption('expires');
        Assert::string($expires);

        try {
            $expiresAt = new \DateTimeImmutable($expires);
        } catch (\Exception) {
            $expiresAt = null;
        }

        if (null === $expiresAt || $expiresAt->getTimestamp() <= time()) {
            $output->writeln(sprintf('<error>The expiry "%s" is invalid or not in the future</error>', $expires));

            return Command::INVALID;
        }

        $query = $this->consentOverrideSigner->sign([] === $categories ? '1' : array_fill_keys($categories, '1'), $expiresAt);

        $output->writeln((string) UriModifier::appendQuery(Uri::createFromString($url), http_build_query($query, '', '&')));

        return Command::SUCCESS;
    }
}
