<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Widget;

interface ConsentWidgetInterface
{
    /**
     * Returns true if the consent widget has been shown before
     */
    public function hasBeenShown(): bool;

    public function setShown(bool $shown = true): void;
}
