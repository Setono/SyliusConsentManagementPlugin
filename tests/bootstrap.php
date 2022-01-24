<?php

declare(strict_types=1);

require_once 'Application/config/bootstrap.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\Setono\SyliusConsentManagementPlugin\Application\Kernel;

$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

$application->run(new ArrayInput([
    'command' => 'doctrine:database:drop',
    '--if-exists' => '1',
    '--force' => '1',
]));

$application->run(new ArrayInput([
    'command' => 'doctrine:database:create',
]));

$application->run(new ArrayInput([
    'command' => 'doctrine:schema:create',
]));

$kernel->shutdown();
