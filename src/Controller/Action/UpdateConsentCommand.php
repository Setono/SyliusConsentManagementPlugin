<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Controller\Action;

final class UpdateConsentCommand
{
    public bool $preferences = false;

    public bool $statistics = false;

    public bool $marketing = false;
}
