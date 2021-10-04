<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Behat\Page\Shop;

use Sylius\Behat\Page\Shop\HomePage as BaseHomePage;
use Webmozart\Assert\Assert;

// todo should probably implement an interface
class HomePage extends BaseHomePage
{
    public function hasInitialDialog(): bool
    {
        return $this->hasElement('initial_dialog');
    }

    public function isInitialDialogVisible(): bool
    {
        return $this->getElement('initial_dialog')->isVisible();
    }

    public function hasSettingsDialog(): bool
    {
        return $this->hasElement('settings_dialog');
    }

    public function isSettingsDialogVisible(): bool
    {
        return $this->getElement('settings_dialog')->isVisible();
    }

    public function initialDialogHides(): bool
    {
        $res = $this->getDocument()->waitFor(5, function () {
            return $this->hasInitialDialog() && !$this->isInitialDialogVisible();
        });

        Assert::boolean($res);

        return $res;
    }

    public function settingsDialogHides(): bool
    {
        $res = $this->getDocument()->waitFor(5, function () {
            return $this->hasSettingsDialog() && !$this->isSettingsDialogVisible();
        });

        Assert::boolean($res);

        return $res;
    }

    public function clickAcceptButton(): void
    {
        $this->getElement('button_accept')->click();

        Assert::true(
            $this->getSession()->wait(10000, "document.cookie.indexOf('sscm_consent_widget=1') >= 0"),
            'The clicking on the accept button did not result in a cookie being set'
        );
    }

    public function clickMoreInformationButton(): void
    {
        $this->getElement('button_more_information')->click();
    }

    public function uncheckAll(): void
    {
        $this->getElement('checkbox_preferences')->uncheck();
        $this->getElement('checkbox_marketing')->uncheck();
        $this->getElement('checkbox_statistics')->uncheck();
    }

    public function checkMarketing(): void
    {
        $this->getElement('checkbox_marketing')->check();
    }

    public function isPreferencesGranted(): bool
    {
        return $this->javascriptVariableIsTrue('scriptFilePreferencesGranted')
            && $this->javascriptVariableIsTrue('inlinePreferencesGranted');
    }

    public function isMarketingGranted(): bool
    {
        return $this->javascriptVariableIsTrue('scriptFileMarketingGranted')
            && $this->javascriptVariableIsTrue('inlineMarketingGranted');
    }

    public function isStatisticsGranted(): bool
    {
        return $this->javascriptVariableIsTrue('scriptFileStatisticsGranted')
            && $this->javascriptVariableIsTrue('inlineStatisticsGranted');
    }

    public function isGoogleTagManagerPreferencesGranted(): bool
    {
        return $this->javascriptVariableIsTrue('googleTagManagerPreferences');
    }

    public function isGoogleTagManagerMarketingGranted(): bool
    {
        return $this->javascriptVariableIsTrue('googleTagManagerMarketing');
    }

    public function isGoogleTagManagerStatisticsGranted(): bool
    {
        return $this->javascriptVariableIsTrue('googleTagManagerStatistics');
    }

    protected function javascriptVariableIsTrue(string $variable): bool
    {
        return $this->getSession()->wait(10000, "window.hasOwnProperty('$variable') && true === window['$variable'];");
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            // elements regarding the consent dialog
            'initial_dialog' => '#sscm-initial-dialog',
            'settings_dialog' => '#sscm-settings-dialog',
            'button_accept' => '[data-test-sscm-btn-submit]',
            'button_more_information' => '.sscm-btn-more-information',
            'checkbox_preferences' => '#setono_sylius_consent_management_consent_preferencesGranted',
            'checkbox_marketing' => '#setono_sylius_consent_management_consent_marketingGranted',
            'checkbox_statistics' => '#setono_sylius_consent_management_consent_statisticsGranted',
        ]);
    }
}
