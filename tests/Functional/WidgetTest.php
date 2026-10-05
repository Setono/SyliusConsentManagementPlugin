<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Functional;

use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\WebDriver;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

final class WidgetTest extends PantherTestCase
{
    public static function setUpBeforeClass(): void
    {
        exec(sprintf('%s/vendor/bin/bdi detect drivers', getcwd()));
    }

    /**
     * @test
     */
    public function it_accepts_all_consents(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        $client->getCrawler()->selectButton('Accept all')->click();

        self::assertSelectorIsNotVisible('.sscm-widget-container');

        $client->wait(15)->until(static function (WebDriver $webDriver): bool {
            foreach ($webDriver->manage()->getCookies() as $cookie) {
                // todo get the cookie name from the container
                if ($cookie->getName() === 'sscm_widget') {
                    return true;
                }
            }

            return false;
        });

        /** @var list<array{level: string, message: string}> $log */
        $log = [];

        $client->wait(15)->until(static function (WebDriver $webDriver) use (&$log): bool {
            // Reading the log clears it, so the entries are collected across the attempts
            /** @var list<array{level: string, message: string}> $entries */
            $entries = $webDriver->manage()->getLog('browser');
            $log = [...$log, ...$entries];

            $neededLogs = [
                'Tag manager loaded',
                'Inline script: Functional granted',
                'Inline script: Marketing granted',
                'Inline script: Statistical granted',
                'Google tag manager: Functional granted',
                'Google tag manager: Marketing granted',
                'Google tag manager: Statistical granted',
                'Script file: Functional granted',
                'Script file: Marketing granted',
                'Script file: Statistical granted',
                'Script with attributes: Marketing granted',
            ];

            foreach ($neededLogs as $neededLog) {
                $found = false;

                foreach ($log as $entry) {
                    if ('INFO' === $entry['level'] && str_contains($entry['message'], $neededLog)) {
                        $found = true;
                    }
                }

                if (!$found) {
                    return false;
                }
            }

            return true;
        });

        // The activated script keeps its attributes, except type and data-sscm-*
        $attributes = $client->executeScript(<<<'JS'
            const script = document.getElementById('script-with-attributes');

            return Object.fromEntries([...script.attributes].map((attribute) => [attribute.name, attribute.value]));
            JS);
        self::assertIsArray($attributes);
        ksort($attributes);
        self::assertSame([
            'crossorigin' => 'anonymous',
            'data-test' => 'kept',
            'id' => 'script-with-attributes',
            'src' => '/js/script-with-attributes.js',
        ], $attributes);

        // Activated external scripts aren't async, so they run in order, whether their src is in data-sscm-src or in the src attribute
        self::assertSame([false, false, false, false], $client->executeScript(<<<'JS'
            return ['functional.js', 'marketing.js', 'statistical.js', 'script-with-attributes.js']
                .map((file) => document.querySelector(`script[src*="/js/${file}"]`).async);
            JS));
    }

    /**
     * @test
     */
    public function it_exposes_the_consent_manager_to_scripts_on_page_load(): void
    {
        $client = self::createClientForNewVisitor();

        // Set by an inline script listening for the sscm:ready event
        self::assertSame(['necessary'], $client->executeScript('return window.consentedCategoriesWhenReady;'));

        // Set by a consent-gated inline script that the manager activates on page load
        self::assertTrue($client->executeScript('return window.necessaryGrantedWhenActivated;'));
    }

    /**
     * @test
     */
    public function it_exposes_the_consent_to_scripts_that_run_after_the_consent_events(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        // Registered before the visitor consents, so the callback is called when they do
        self::assertFalse($client->executeScript(<<<'JS'
            window.marketingGranted = false;
            window.sscmManager.whenGranted('marketing', () => { window.marketingGranted = true; });

            return window.marketingGranted;
            JS));

        $client->getCrawler()->selectButton('Accept all')->click();

        // The consent is updated when the server has saved it
        $client->wait(15)->until(static fn (JavaScriptExecutor $webDriver): bool => true === $webDriver->executeScript(
            'return window.marketingGranted;',
        ));

        self::assertTrue($client->executeScript('return window.sscmManager.isGranted("marketing");'));
        self::assertEqualsCanonicalizing(
            ['functional', 'marketing', 'statistical', 'necessary'],
            $client->executeScript('return window.sscmManager.getConsentedCategories();'),
        );

        // A script running after the consent events were dispatched still gets notified
        self::assertTrue($client->executeScript(<<<'JS'
            let called = false;
            window.sscmManager.whenGranted('marketing', () => { called = true; });

            return called;
            JS));
    }

    /**
     * @test
     */
    public function it_only_grants_the_selected_categories(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        $client->executeScript(<<<'JS'
            window.grantedCategories = [];
            ['marketing', 'statistical'].forEach((category) => {
                window.sscmManager.whenGranted(category, () => window.grantedCategories.push(category));
            });

            document.addEventListener('sscm:consent:updated', () => { window.consentUpdated = true; });
            JS);

        $client->getCrawler()->filter('.sscm-widget-container input[type="checkbox"][value="statistical"]')->click();
        $client->getCrawler()->selectButton('Accept selected')->click();

        $client->wait(15)->until(static fn (JavaScriptExecutor $webDriver): bool => true === $webDriver->executeScript(
            'return window.consentUpdated;',
        ));

        self::assertSame(['statistical'], $client->executeScript('return window.grantedCategories;'));
        self::assertFalse($client->executeScript('return window.sscmManager.isGranted("marketing");'));
        self::assertTrue($client->executeScript('return window.sscmManager.isGranted("statistical");'));
    }

    /**
     * @test
     */
    public function it_shows_the_widget_again_when_the_consent_cannot_be_saved(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        $getCheckboxStates = 'return [...document.querySelectorAll(".sscm-widget-container input[type=checkbox]")].map((checkbox) => checkbox.checked);';
        $checkboxStates = $client->executeScript($getCheckboxStates);
        self::assertIsArray($checkboxStates);
        self::assertContains(false, $checkboxStates);

        $client->executeScript(<<<'JS'
            window.originalFetch = window.fetch;
            window.fetch = () => Promise.reject(new TypeError('Failed to fetch'));
            JS);

        $client->getCrawler()->selectButton('Accept all')->click();

        self::assertSelectorWillBeVisible('.sscm-error');
        self::assertSelectorIsVisible('.sscm-widget-container');
        self::assertSelectorAttributeContains('.sscm-error', 'role', 'alert');
        self::assertSelectorTextContains('.sscm-error', 'An error was encountered while saving your choices');

        // 'Accept all' checked every checkbox. A retry with 'Accept selected' must not grant all categories
        self::assertSame($checkboxStates, $client->executeScript($getCheckboxStates));

        self::assertTrue($client->executeScript(
            'return document.activeElement === document.querySelector(".sscm-widget-container button[data-action]");',
        ));
        self::assertFalse($client->executeScript('return window.sscmManager.isGranted("marketing");'));

        // Retrying works when the consent can be saved again
        $client->executeScript('window.fetch = window.originalFetch;');
        $client->getCrawler()->selectButton('Accept all')->click();

        $client->wait(15)->until(static fn (JavaScriptExecutor $webDriver): bool => true === $webDriver->executeScript(
            'return window.sscmManager.isGranted("marketing");',
        ));

        self::assertSelectorIsNotVisible('.sscm-widget-container');
        self::assertTrue($client->executeScript('return document.querySelector(".sscm-error").hidden;'));
    }

    /**
     * @test
     */
    public function it_creates_the_data_layer_if_it_does_not_exist(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        // As if the tag manager defines the data layer later
        $client->executeScript('delete window.dataLayer;');

        $client->getCrawler()->selectButton('Accept all')->click();

        $client->wait(15)->until(static fn (JavaScriptExecutor $webDriver): bool => true === $webDriver->executeScript(
            'return Array.isArray(window.dataLayer) && window.dataLayer.some((item) => "sscm:consent:marketing:granted" === item.event);',
        ));
    }

    /**
     * Panther reuses the browser between the tests in a class, so every test starts without the cookies and the
     * browser log of the previous tests
     */
    private static function createClientForNewVisitor(): Client
    {
        $client = static::createPantherClient(managerOptions: [
            'capabilities' => [
                'goog:loggingPrefs' => [
                    'browser' => 'ALL', // calls to console.* methods
                ],
            ],
        ]);

        // A static file runs no scripts, so nothing is logged after the log is cleared
        $client->request('GET', '/robots.txt');
        $client->manage()->deleteAllCookies();
        $client->manage()->getLog('browser'); // Reading the log clears it

        $client->request('GET', '/');

        return $client;
    }
}
