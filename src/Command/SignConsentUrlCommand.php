<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

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
    /**
     * A signed URL can't be revoked, short of changing the kernel secret, so it can't be valid for longer than this
     */
    private const MAX_LIFETIME_IN_HOURS = 24;

    public function __construct(private readonly ConsentOverrideSignerInterface $consentOverrideSigner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'The URL to sign, e.g. https://example.com/en_US/')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The code of a category to grant consent for. Grants consent for all categories if omitted')
            ->addOption('expires', null, InputOption::VALUE_REQUIRED, sprintf('When the URL stops granting consent, at most %d hours from now, e.g. "+30 minutes" or "+8 hours"', self::MAX_LIFETIME_IN_HOURS), '+1 hour')
            ->setHelp(sprintf(<<<'HELP'
The <info>%%command.name%%</info> command prints a URL that grants consent through a signed <comment>_consent</comment> query parameter, e.g. for QA or tag audits in production:

  <info>php %%command.full_name%% https://example.com/en_US/</info>

Without <comment>--category</comment>, the URL grants consent for all categories. The consent only applies to the request for that URL, so append the signed query parameters to every page you visit.

A signed URL can't be revoked short of changing the kernel secret (<comment>APP_SECRET</comment>), so it's valid for an hour by default and for at most %d hours (<comment>--expires</comment>).
HELP, self::MAX_LIFETIME_IN_HOURS))
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

        if ($expiresAt->getTimestamp() > time() + self::MAX_LIFETIME_IN_HOURS * 3600) {
            $output->writeln(sprintf(
                '<error>The expiry "%s" is more than %d hours from now. A signed URL can\'t be revoked, so it can be valid for %d hours at most</error>',
                $expires,
                self::MAX_LIFETIME_IN_HOURS,
                self::MAX_LIFETIME_IN_HOURS,
            ));

            return Command::INVALID;
        }

        $output->writeln($this->consentOverrideSigner->signUrl($url, [] === $categories ? '1' : array_fill_keys($categories, '1'), $expiresAt));

        return Command::SUCCESS;
    }
}
