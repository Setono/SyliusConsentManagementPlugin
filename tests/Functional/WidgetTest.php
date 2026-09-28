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
        $client = static::createPantherClient(managerOptions: [
            'capabilities' => [
                'goog:loggingPrefs' => [
                    'browser' => 'ALL', // calls to console.* methods
                ],
            ],
        ]);
        $client->request('GET', '/');
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

        $client->wait(15)->until(static function (WebDriver $webDriver): bool {
            /** @var list<array{level: string, message: string}> $log */
            $log = $webDriver->manage()->getLog('browser');

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
    }

    /**
     * @test
     */
    public function it_exposes_the_consent_to_scripts_that_run_after_the_consent_events(): void
    {
        $client = self::createClientForNewVisitor();
        self::assertSelectorExists('.sscm-widget-container');

        $client->getCrawler()->selectButton('Accept all')->click();

        // The consent is updated when the server has saved it
        $client->wait(15)->until(static fn (JavaScriptExecutor $webDriver): bool => true === $webDriver->executeScript(
            'return window.sscmManager.isGranted("marketing");',
        ));

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

        $client->getCrawler()->selectButton('Accept selected')->click();

        $client->wait(15)->until(static function (WebDriver $webDriver): bool {
            foreach ($webDriver->manage()->getCookies() as $cookie) {
                if ($cookie->getName() === 'sscm_widget') {
                    return true;
                }
            }

            return false;
        });

        self::assertFalse($client->executeScript('return window.sscmManager.isGranted("marketing");'));
        self::assertFalse($client->executeScript('return window.sscmManager.isGranted("statistical");'));
    }

    /**
     * Panther reuses the browser between the tests in a class, so the cookies set by a previous test are removed
     */
    private static function createClientForNewVisitor(): Client
    {
        $client = static::createPantherClient();
        $client->request('GET', '/');
        $client->manage()->deleteAllCookies();
        $client->request('GET', '/');

        return $client;
    }
}
