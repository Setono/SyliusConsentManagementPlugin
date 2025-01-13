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

**NOTICE** that this plugin uses the `twig/markdown-extra`, `twig/extra-bundle`, and `league/commonmark` to render the widget with Markdown.
It should work out of the box with the Symfony Flex recipe, but if you're not using Symfony Flex, you should install the bundle manually.

### Register bundle

Add the bundle to `bundles.php` if not done automatically. Be sure to add this line **before** `Sylius\Bundle\GridBundle\SyliusGridBundle::class`

```php
    Setono\SyliusConsentManagementPlugin\SetonoSyliusConsentManagementPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
```

Create the file `config/packages/setono_sylius_cookie_consent.yaml` and add the following:

```yaml
# config/packages/setono_sylius_cookie_consent.yaml
imports:
    - { resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/app/config.yaml" }

    # Uncomment next line if you want some default fixtures for this plugin
    # - { resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/app/fixtures.yaml" }

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
php bin/console doctrine:migration:diff
php bin/console doctrine:migration:migrate
```

### Import the routes

Create the file `config/routes/setono_sylius_cookie_consent.yaml` and add the following:

```yaml
# config/routes/setono_sylius_cookie_consent.yaml
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

[ico-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/workflows/build/badge.svg

[link-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/actions
