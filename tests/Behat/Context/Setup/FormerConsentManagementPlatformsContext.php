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

    /**
     * @Given the visitor has consented to all services through Cookie Information before
     */
    public function theVisitorHasConsentedThroughCookieInformation(): void
    {
        $this->session->setCookie('CookieInformationConsent', '%7B%22website_uuid%22%3A%225b176008-519d-487f-88d6-54e82ed10056%22%2C%22timestamp%22%3A%222021-10-04T12%3A41%3A26.757Z%22%2C%22consent_url%22%3A%22https%3A%2F%2Fwww.wattoo.dk%2F%22%2C%22consent_website%22%3A%22wattoo.dk%22%2C%22consent_domain%22%3A%22www.wattoo.dk%22%2C%22user_uid%22%3A%22f608b9f5-7960-4a63-909e-68f11a15b326%22%2C%22consents_approved%22%3A%5B%22cookie_cat_necessary%22%2C%22cookie_cat_functional%22%2C%22cookie_cat_statistic%22%2C%22cookie_cat_marketing%22%2C%22cookie_cat_unclassified%22%5D%2C%22consents_denied%22%3A%5B%5D%2C%22user_agent%22%3A%22Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_15_7%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F94.0.4606.61%20Safari%2F537.36%22%7D');
    }
}
