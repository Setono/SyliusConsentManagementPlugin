<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Tests\Setono\SyliusConsentManagementPlugin\Behat\Page\Shop\HomePage;
use Webmozart\Assert\Assert;

final class ConsentDialogContext implements Context
{
    private HomePage $homePage;

    public function __construct(HomePage $homePage)
    {
        $this->homePage = $homePage;
    }

    /**
     * @When I see the consent dialog
     */
    public function iShouldSeeAConsentDialog(): void
    {
        Assert::true($this->homePage->hasConsentDialog(), 'No consent dialog in the HTML');
        Assert::true($this->homePage->isConsentDialogVisible(), 'The consent dialog is not visible');
    }

    /**
     * @When I click the accept button
     */
    public function iShouldBeAbleToClickAnAcceptButton(): void
    {
        $this->homePage->clickAcceptButton();
    }

    /**
     * @Then the consent dialog should disappear
     */
    public function theConsentDialogShouldDisappear(): void
    {
        Assert::true($this->homePage->consentDialogHides(), 'The consent dialog did not disappear');
    }

    /**
     * @Then all services should run
     */
    public function allServicesShouldRun(): void
    {
        Assert::true($this->homePage->isPreferencesGranted(), 'The preference consent was not granted');
        Assert::true($this->homePage->isMarketingGranted(), 'The marketing consent was not granted');
        Assert::true($this->homePage->isStatisticsGranted(), 'The statistics consent was not granted');
    }
}
