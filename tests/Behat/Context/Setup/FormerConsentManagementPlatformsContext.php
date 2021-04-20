<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;

final class FormerConsentManagementPlatformsContext implements Context
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @Given the visitor has consented to all services through Cookiebot before
     */
    public function theVisitorHasConsentedThroughCookiebot(): void
    {
        $this->session->setCookie('CookieConsent', '{stamp:%27SOyYBJ0zEzmM3TdEMGrRFeCnfXscCiPpTRrBpcgLRGIF5lKplAkvkA==%27%2Cnecessary:true%2Cpreferences:true%2Cstatistics:true%2Cmarketing:true%2Cver:1%2Cutc:1618899306192%2Cregion:%27dk%27}');
    }
}
