<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-consent-management:prune-cookies',
    description: 'Will remove cookies that have not been confirmed before the "pruning threshold"',
)]
final class PruneCookiesCommand extends Command
{
    public function __construct(private readonly CookieRepositoryInterface $cookieRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cookieRepository->prune();

        return 0;
    }
}
