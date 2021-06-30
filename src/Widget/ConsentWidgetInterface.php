<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Widget;

interface ConsentWidgetInterface
{
    /**
     * Returns true if the consent dialog should be shown
     */
    public function show(): bool;

    public function setShow(bool $show): void;
}
