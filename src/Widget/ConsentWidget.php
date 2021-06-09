<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Widget;

final class ConsentWidget implements ConsentWidgetInterface
{
    private bool $shown = false;

    public function hasBeenShown(): bool
    {
        return $this->shown;
    }

    public function setShown(bool $shown = true): void
    {
        $this->shown = $shown;
    }
}
