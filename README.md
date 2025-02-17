# Sylius Consent Management Plugin

[![Build Status][ico-github-actions]][link-github-actions]

This plugin will create a consent dialog that every user will see. The user is given the option to accept all 'services'
or edit their choice by clicking a 'More information' button.

Inspiration to some choices made in this plugin is based on the guidelines in this document: https://gdpr.eu/cookies.

## Installation

```shell
composer require setono/sylius-consent-management-plugin
```

**NOTICE** that this plugin uses the `twig/markdown-extra`, `twig/extra-bundle`, and `league/commonmark` to render the widget with Markdown.
It should work out of the box with the Symfony Flex recipe, but if you're not using Symfony Flex, you should install the bundle manually.

### Register bundle

Add the bundle to `bundles.php` if not done automatically. Be sure to add this line **before** `Sylius\Bundle\GridBundle\SyliusGridBundle::class`

```php
    Setono\SyliusConsentManagementPlugin\SetonoSyliusConsentManagementPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
```

### Build assets

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

### Update the layout

You need to inject the consent widget somewhere in your page (preferably before `</body>`):

```html
<!-- ... -->

{{ sscm_widget() }}
</body>
</html>
```

[ico-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/workflows/build/badge.svg

[link-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/actions
