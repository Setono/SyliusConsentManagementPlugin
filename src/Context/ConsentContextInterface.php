<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Context;

use Setono\SyliusCookieConsentPlugin\Model\Consent;

interface ConsentContextInterface
{
    public function get(): Consent;
}
