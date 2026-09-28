<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Composer\InstalledVersions;
use Symfony\Component\Panther\Client;
use Symfony\Component\Process\ExecutableFinder;
use Webmozart\Assert\Assert;

final class PantherClientFactory implements PantherClientFactoryInterface
{
    public function __construct(
        private readonly string $driverDirectory,
    ) {
    }

    public function create(): Client
    {
        return Client::createChromeClient($this->resolveDriver());
    }

    private function resolveDriver(): string
    {
        $driver = (new ExecutableFinder())->find('chromedriver', null, [$this->driverDirectory]);
        if (null !== $driver) {
            return $driver;
        }

        $installedVersionsFile = (new \ReflectionClass(InstalledVersions::class))->getFileName();
        Assert::string($installedVersionsFile);

        $vendorDir = dirname($installedVersionsFile, 2);
        exec(sprintf('%s/bin/bdi detect %s', $vendorDir, $this->driverDirectory));

        $driver = (new ExecutableFinder())->find('chromedriver', null, [$this->driverDirectory]);
        if (null === $driver) {
            throw new \RuntimeException('Could not find chromedriver');
        }

        return $driver;
    }
}
