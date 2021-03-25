<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Behat\Page\Shop;

use Sylius\Behat\Page\Shop\HomePage as BaseHomePage;
use Webmozart\Assert\Assert;

// todo should probably implement an interface
class HomePage extends BaseHomePage
{
    public function hasConsentDialog(): bool
    {
        return $this->hasElement('consent_dialog');
    }

    public function isConsentDialogVisible(): bool
    {
        return $this->getElement('consent_dialog')->isVisible();
    }

    public function consentDialogHides(): bool
    {
        $res = $this->getDocument()->waitFor(5, function () {
            return $this->hasConsentDialog() && !$this->isConsentDialogVisible();
        });

        Assert::boolean($res);

        return $res;
    }

    public function clickAcceptButton(): void
    {
        $this->getElement('button_accept')->click();
    }

    public function isPreferencesGranted(): bool
    {
        return $this->hasElement('preferences_element');
    }

    public function isMarketingGranted(): bool
    {
        return $this->hasElement('marketing_element');
    }

    public function isStatisticsGranted(): bool
    {
        return $this->hasElement('statistics_element');
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'consent_dialog' => '.sscm-consent-container',
            'button_accept' => '.sscm-btn-submit',
            'preferences_element' => 'div.sscm-preferences-granted',
            'marketing_element' => 'div.sscm-marketing-granted',
            'statistics_element' => 'div.sscm-statistics-granted',
        ]);
    }
}
