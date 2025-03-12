<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Functional;

use Facebook\WebDriver\WebDriver;
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
}
