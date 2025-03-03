<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Command;

use Setono\SyliusConsentManagementPlugin\EmailManager\CookieEmailManagerInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-consent-management:stale-cookies',
    description: 'Will send an email to the store owner about stale cookies',
)]
final class StaleCookiesCommand extends Command
{
    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly CookieEmailManagerInterface $cookieEmailManager,
        private readonly string $staleThreshold,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cookies = $this->cookieRepository->findStaleCookies($this->staleThreshold);
        if ([] === $cookies) {
            $output->writeln('No stale cookies found');

            return 0;
        }

        $this->cookieEmailManager->sendStaleCookiesEmail($cookies);

        return 0;
    }
}
