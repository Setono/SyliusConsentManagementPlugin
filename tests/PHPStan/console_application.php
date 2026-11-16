<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use Tests\Setono\SyliusConsentManagementPlugin\Application\Kernel;

require __DIR__ . '/../../vendor/autoload.php';

// PHPStan instantiates the commands, so the env vars their services need (like APP_SECRET) must be set
(new Dotenv())->loadEnv(__DIR__ . '/../Application/.env', null, 'test');

$kernel = new Kernel('test', true);
$kernel->boot();

return new Application($kernel);
