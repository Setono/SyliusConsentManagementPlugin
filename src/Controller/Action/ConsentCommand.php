<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Action;

final class ConsentCommand
{
    public bool $preferences = true;

    public bool $statistics = true;

    public bool $marketing = true;
}
