<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Context;

use Setono\SyliusConsentManagementPlugin\Model\Consent;

interface ConsentContextInterface
{
    public function get(): Consent;
}
