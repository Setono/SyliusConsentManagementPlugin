# Sylius Consent Management Plugin

[![Build Status][ico-github-actions]][link-github-actions]

This plugin will create a consent dialog that every user will see. The user is given the option to accept all 'services'
or edit their choice by clicking a 'More information' button.

Inspiration to some choices made in this plugin is based on the guidelines in this document: https://gdpr.eu/cookies.

## Installation

Install the package via composer:

```shell
composer require setono/sylius-consent-management-plugin
```

### Register bundle

Add the bundle to `bundles.php` if not done automatically. Be sure to add this line **before** `Sylius\Bundle\GridBundle\SyliusGridBundle::class`

```php
    Setono\SyliusConsentManagementPlugin\SetonoSyliusConsentManagementPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
```

Create the file `config/packages/setono_sylius_cookie_consent.yaml` and add the following:

```yaml
imports:
    - { resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/app/config.yaml" }

setono_sylius_consent_management:
    notify:
        - "johndoe@setono.com"
```

### Build assets

```shell
php bin/console assets:install
php bin/console sylius:theme:assets:install
```

### Add migration

```shell
php bin/console doctrine:migration:difference
php bin/console doctrine:migration:migrate
```

### Import the routes

Create the file `config/routes/setono_sylius_cookie_consent.yaml` and add the following:

```yaml
setono_sylius_consent_management:
    resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/routes.yaml"
```

### Update the layout

From the test layout, you can copy those important parts 

```html
<head>
    <!-- ... -->
    {{ sscm_consent_tag() }}
    <!-- ... -->
</head>

{% block footer %}
    <!-- ... -->
    {{ render(path('setono_sylius_consent_management_shop_partial_consent_widget')) }}
    <!-- ... -->
{% endblock %}
    
{% block javascripts %}
    <!-- ... -->
    <script src="{{ asset('bundles/setonosyliusconsentmanagementplugin/js/consent-widget.js') }}" async></script>
    <!-- ... -->
{% endblock %}
```

## Development

### Behat
1. Create an alias like: `chrome: aliased to /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome`
2. Run `chrome --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1`
3. Run `APP_ENV=test symfony server:start --port=8080 --dir=public --daemon`
4. Run `vendor/bin/behat --strict -vvv`

[ico-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/workflows/build/badge.svg

[link-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/actions
