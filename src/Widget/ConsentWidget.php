<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Widget;

final class ConsentWidget implements ConsentWidgetInterface
{
    private bool $show = true;

    public function show(): bool
    {
        return $this->show;
    }

    public function setShow(bool $show = false): void
    {
        $this->show = $show;
    }
}
