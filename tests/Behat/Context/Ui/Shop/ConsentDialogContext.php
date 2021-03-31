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
     * @Then the consent dialog should not be visible
     */
    public function theConsentDialogShouldNotBeVisible(): void
    {
        Assert::false($this->homePage->hasConsentDialog(), 'There was a consent dialog in the HTML');
    }

    /**
     * @When I click the accept button
     */
    public function iClickAcceptButton(): void
    {
        $this->homePage->clickAcceptButton();
    }

    /**
     * @When I click the more information button
     */
    public function iClickMoreInformationButton(): void
    {
        $this->homePage->clickMoreInformationButton();
    }

    /**
     * @When I only check marketing services
     */
    public function iOnlyCheckMarketing(): void
    {
        $this->homePage->uncheckAll();
        $this->homePage->checkMarketing();
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

    /**
     * @Then only marketing services should run
     */
    public function onlyMarketingServicesShouldRun(): void
    {
        Assert::false($this->homePage->isPreferencesGranted(), 'The preference consent was granted');
        Assert::true($this->homePage->isMarketingGranted(), 'The marketing consent was not granted');
        Assert::false($this->homePage->isStatisticsGranted(), 'The statistics consent was granted');
    }
}
