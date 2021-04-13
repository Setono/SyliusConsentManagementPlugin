<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EmailManager;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;

interface CookieEmailManagerInterface
{
    /**
     * @param array<array-key, CookieInterface> $cookies
     */
    public function sendNewCookiesEmail(array $cookies): void;
}
