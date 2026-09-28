# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Sylius plugin (`setono/sylius-consent-management-plugin`) that renders a cookie consent widget in the shop, records consent per client, and discovers and catalogues the cookies a store sets. It supports PHP >= 8.1 and Symfony 6.4/7. CI runs PHP 8.1–8.3, and coding standards run on 8.1, so code must stay 8.1-compatible (Rector is set to `UP_TO_PHP_81`).

## Commands

```bash
composer analyse                  # PHPStan, level max (phpstan.neon)
composer check-style              # ECS (sylius-labs/coding-standard)
composer fix-style                # ECS with --fix
vendor/bin/rector process --dry-run
composer normalize --dry-run && composer validate --strict

vendor/bin/phpunit                                   # default suite = "unit" (everything in tests/ except tests/Functional)
vendor/bin/phpunit tests/Checker/RequestBasedConsentCheckerTest.php
vendor/bin/phpunit --filter it_grants_all tests/Checker/RequestBasedConsentCheckerTest.php
vendor/bin/phpunit --testsuite functional            # Panther/Chrome browser tests, see below
vendor/bin/infection                                 # mutation testing (minMsi in infection.json.dist)

# Lint plugin resources through the test app
(cd tests/Application && bin/console lint:yaml ../../src/Resources)
(cd tests/Application && bin/console lint:twig ../../src/Resources)
(cd tests/Application && bin/console lint:container)
```

CI also runs `vendor/bin/composer-dependency-analyser`, configured in `composer-dependency-analyser.php`. CI runs it with `require-dev` removed, so run locally it reports false "unused" Sylius packages, because `sylius/sylius` provides them in dev. If you add a runtime dependency that is only wired through config, whitelist it there.

### Static analysis

PHPStan runs at level max with no baseline. It boots the test app kernel through `tests/PHPStan/{console_application,object_manager}.php`. `phpstan.neon` ignores the noise identifiers Setono plugins ignore by convention: `doctrine.columnType`, `doctrine.associationType`, `missingType.generics` and `missingType.iterableValue`. Don't add value-free phpdoc to silence those. Symfony's `FormView::$vars` is untyped, so form types read it into a local `@var`-annotated array and assign it back (see `CategoryChoiceType`). Import `Sylius\Resource\Model\TranslatableTrait`, not the deprecated `Sylius\Component\Resource\Model` alias: PHPStan can't reliably resolve the `getTranslation as private doGetTranslation` alias through it.

### Test application

`tests/Application` is a full Sylius 1.14 app (Kernel, config, templates), kept in line with the `1.14.x` branch of [Setono/SyliusPluginSkeleton](https://github.com/Setono/SyliusPluginSkeleton/tree/1.14.x). It's used for the functional tests, for linting, for PHPStan, and for manual testing. `tests/Application/.env` holds `DATABASE_URL`, which points to a local MySQL/MariaDB; override it with a real env var to use another server. Node is pinned in `tests/Application/.nvmrc`. Setting it up for functional tests follows the CI order:

```bash
(cd tests/Application && nvm use && yarn install && yarn build)
(cd tests/Application && bin/console doctrine:database:create && bin/console doctrine:schema:create)
(cd tests/Application && bin/console sylius:fixtures:load -n)   # or: composer fixtures
(cd tests/Application && bin/console assets:install)
vendor/bin/phpunit --testsuite functional
```

`tests/Functional/WidgetTest.php` drives Chrome through Symfony Panther. `bdi` downloads chromedriver into `drivers/`, which is gitignored. The test clicks "Accept all" and asserts on browser console logs emitted by `tests/Application/public/js/*.js` and the inline scripts in `tests/Application/templates/bundles/SyliusShopBundle/layout.html.twig`. Failure screenshots go to `var/error-screenshots/`.

## Test conventions

- PHPUnit tests use the `/** @test */` annotation with snake_case `it_...` method names, and use Prophecy (`ProphecyTrait`) for doubles, not `createMock`.
- Use `self::assertX()`, not `$this->assertX()`; PHPStan's strict rules flag dynamic calls to static assertions. Data providers must be `public static`.
- Twig functions are tested with `Twig\Test\IntegrationTestCase` and `.test` fixture files in `tests/Twig/*Fixtures/functions/`. Add a fixture file rather than a PHP test method.
- `tests/DependencyInjection` uses `AbstractExtensionTestCase` (matthiasnoback).

## Architecture

### Wiring

- `SetonoSyliusConsentManagementPlugin` is an `AbstractResourceBundle` with ORM only. It registers two `CompositeCompilerPass`es (from setono/composite-compiler-pass) for widget display deciders and crawler URL providers. Their tags are `setono_sylius_consent_management.widget_display_decider` and `setono_sylius_consent_management.url_provider`. The extension autoconfigures both tags from their interfaces.
- Services are defined explicitly in XML, one file per area, in `src/Resources/config/services/*.xml`, all imported by `services.xml`. Register new services there.
- `SetonoSyliusConsentManagementExtension::prepend()` defines much of the plugin's config in PHP:
  - the cookie workflow (framework `workflows`)
  - **all admin grids** (`sylius_grid`), so there are no grid YAML files
  - the two emails (`sylius_mailer`)
  - admin template events (`sylius_ui`)
- Because the grids are prepended, the README requires registering the bundle **before** `SyliusGridBundle` in `bundles.php`.
- Sylius resources: `category`, `service`, `cookie` (these three are translatable), `consent_entry` and `widget_config`. Their classes are overridable via the `resources` config node. Doctrine mappings live in `Resources/config/doctrine/model/*.orm.xml` and validation in `Resources/config/validation/*.xml`.

### Domain model

- `Category` (code, `necessary` flag, position) has many `Service`s, and each `Service` has many `Cookie`s. Services and cookies are channel-scoped.
- `ConsentEntry` records one consent submission. It is keyed by `clientId` from setono/client-bundle and stores the consented category **codes as a list of strings**, not as relations.
- `WidgetConfig` holds the widget text, labels and layout per channel and locale.

### Consent checking

The plugin decorates `Setono\Consent\ConsentCheckerInterface` from setono/consent-bundle, whose base implementation is `StaticConsentChecker` with configured defaults. The decorators are set up in `services/checker.xml`, and the effective call order, outermost first, is:

1. `CachedConsentChecker`: in-memory cache.
2. `RequestBasedConsentChecker`: the `?_consent=1|0` or `?_consent[marketing]=0` query parameter overrides consent. This is handy for manual testing.
3. `SessionCachedConsentChecker`: caches in the session and is invalidated on the `ConsentUpdated` event.
4. `ORMBasedConsentChecker`: looks up the client's latest `ConsentEntry`.
5. The consent bundle's static defaults.

### Shop widget flow

- `sscm_resources()` goes in `<head>` and renders `shop/resources.html.twig`. That template loads `public/js/consent-manager.js` as an ES module. `sscm_widget()` goes before `</body>` and returns an empty string when the widget shouldn't show.
- The JS in `src/Resources/public/js` is plain ES modules with no build step; `assets:install` publishes it. `consent-manager.js` lazily imports `consent-widget.js` when the widget should display.
- `consent-manager.js` dispatches the `sscm:consent:granted` and `sscm:consent:<category>:granted` DOM events, plus dataLayer events. It also activates scripts rendered by `sscm_script_tag()` / `sscm_script_tag_attributes()`, which gate scripts by category.
- The widget form POSTs to `/ajax/update-consent`, handled by `ConsentController::update`. That action persists a `ConsentEntry`, writes the widget cookie via `WidgetCookieManager`, and dispatches `ConsentUpdated`.
- Whether to display the widget is decided by `CompositeWidgetDisplayDecider`, wrapped in `CachedWidgetDisplayDecider`. It runs the cookie-based decider, then the bot-based one.
- Changing the configured `widget.cookie_value` forces every visitor to consent again.
- Shop routes are `_locale`-prefixed. `routes_no_locale.yaml` is the alternative for stores without locale prefixes.

### Cookie discovery and lifecycle

New `Cookie` entities start in the `pending` state. They are created from three sources:

- **Server-side sampling**: `SampleCookiesServerSideSubscriber` records request cookies on `kernel.request`.
- **Client-side sampling**: `SampleCookiesClientSideSubscriber` injects a script into Chrome responses. The script POSTs `document.cookie` to `/ajax/sample-cookies` (`SampleController`).
- **Crawler**: `setono:sylius-consent-management:crawl` runs a headless Chrome through Panther over URLs from `CompositeUrlProvider` (the homepage, plus each channel's latest product page with tracking parameters like `gclid` and `fbclid` appended). It dispatches `CrawlStarted`, `WillCrawl` and `Crawled`, and `SaveCookiesSubscriber` persists what it finds.

`SampleDecider` gates both sampling paths: the `?_sample=1|0` query parameter forces sampling on or off, and otherwise the `sampling.rate` and `sampling.firewalls` config apply (the default firewall is `shop`).

`CookieWorkflow` is a Symfony Workflow state machine named `setono_sylius_consent_management__cookie` with one transition, `pending → confirmed` (`confirm`). A cookie is confirmed automatically in two cases:

- `ConfirmCookieListener` confirms it once it has at least 3 samples.
- `AutomaticallyConfirmCookieSubscriber` confirms it when an admin creates it.

On confirm, `NotifyAboutCookiesSubscriber` collects the cookies and emails the `notify` addresses, or, if none are configured, the first enabled channel's contact email. The email goes out on kernel or console terminate. The other commands are `setono:sylius-consent-management:stale-cookies` (emails about stale cookies) and `setono:sylius-consent-management:prune-cookies`.
