<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

final class ConsentCommand
{
    public bool $marketingGranted = true;

    public bool $preferencesGranted = true;

    public bool $statisticsGranted = true;
}
