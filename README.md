# Sylius Consent Management Plugin

[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

This plugin will create a consent dialog that every user will see. The user is given the option to accept all 'services'
or necessary services.

## Installation

```shell
composer require setono/sylius-consent-management-plugin
```

### Register bundle

Add the bundle to `bundles.php` if not done automatically. Be sure to add this line **before** `Sylius\Bundle\GridBundle\SyliusGridBundle::class`

```php
    Setono\SyliusConsentManagementPlugin\SetonoSyliusConsentManagementPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
```

### Install assets

```shell
php bin/console assets:install
```

### Add migration

```shell
php bin/console doctrine:migration:diff
php bin/console doctrine:migration:migrate
```

### Import the routes

Create the file `config/routes/setono_sylius_consent_management.yaml` and add the following:

```yaml
# config/routes/setono_sylius_consent_management.yaml
setono_sylius_consent_management:
    resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/routes.yaml"
```

If your store doesn't use locales, there's also a route file for that:

```yaml
# config/routes/setono_sylius_consent_management.yaml
setono_sylius_consent_management:
    resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/routes_no_locale.yaml"
```

### Update the layout

For everything to work there are two Twig functions you need to call: `sscm_resources()` and `sscm_widget()`.
The `sscm_resources()` should be called in your `<head>` section while the `sscm_widget()` should be called just before
the `</body>`.

The `sscm_resources()` function will output the JS and CSS needed to render the widget. It will only output what's
necessary, so you don't have to worry about bloating your page with unused JS or CSS.

The `sscm_widget()` will output the actual HTML needed to render the widget. It's also true for this function that it
will only render what's necessary, so in this case if the user has already seen the widget, it will output an empty string.

## JavaScript API

`sscm_resources()` creates the consent manager as `window.sscmManager`:

```js
window.sscmManager.getConsentedCategories(); // e.g. ['necessary', 'statistical']
window.sscmManager.isGranted('marketing'); // true or false

// Calls back right away if the visitor has consented to marketing, and otherwise when they do
window.sscmManager.whenGranted('marketing', () => {
    // load your marketing script
});
```

The manager dispatches these events on `document` on page load and whenever the visitor updates their consent:

- `sscm:consent:granted`, with the consented categories in `event.detail.categories`
- `sscm:consent:<category>:granted`, e.g. `sscm:consent:marketing:granted`. It's also pushed to the `dataLayer`

Scripts that run after an event was dispatched miss it, so use `whenGranted()` instead.

The manager is a module script, so it only runs after the HTML has been parsed. Scripts that run before it can wait for
`sscm:ready`, which the manager dispatches on `document` once it's ready and has dispatched the events above:

```js
document.addEventListener('sscm:ready', () => {
    window.sscmManager.whenGranted('marketing', () => {
        // ...
    });
});
```

### Options

Define the options before `sscm_resources()`:

```html
<script>
    window.sscmManagerOptions = {
        // The URL of the widget module. Defaults to the asset URL of the plugin's consent-widget.js
        widgetScriptUrl: '/build/my-consent-widget.js',
    };

    // Merged deeply with the defaults, so this keeps the default widget selector and callbacks
    window.sscmWidgetOptions = {
        selector: {
            backdrop: '.my-backdrop',
        },
    };
</script>
```

The widget options are:

- `allowedActions`: the `data-action` values the widget's buttons may have. Defaults to `['acceptAll', 'acceptSelected']`.
  Arrays are replaced, not merged
- `selector.backdrop` and `selector.widget`: default to `.sscm-backdrop` and `.sscm-widget-container`
- `callback.<action>`: called with the widget as `this` before the consent is saved. By default, `acceptAll` checks
  every category checkbox

[ico-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusConsentManagementPlugin/graph/badge.svg?token=C19PGH2X31

[link-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusConsentManagementPlugin
