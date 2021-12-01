Before you can install the plugin you need a packagist token. You get a token once you buy the plugin. If you haven't done so yet, go to [sylius-consent-management.com](https://sylius-consent-management.com) and hit the big buy button ;)

{% hint style="info" %}
If you have Flex installed and just want the plugin installed with the default settings, go directly to [Installing the plugin](#installing-the-plugin)
{% endhint %}

The plugin relies on two bundles providing user identification and basic consent functionality. These are the
[ClientIdBundle](https://github.com/Setono/ClientIdBundle) and the [ConsentBundle](https://github.com/Setono/ConsentBundle) respectively.

The client id bundle will create a cookie (`setono_client_id`) that will allow users of the bundle to easily identity a
visitor (i.e. 'client') with a unique id. The bundle also creates the `setono_client_id.provider.default_client_id`
service which you can use in your application to obtain the client id for the current visitor.

The consent bundle lays the foundation for consent management by creating the service `setono_consent.context.default`
which is what you will use to obtain consent information around your application. The bundle also allows you to create
defaults for the three consent categories (namely marketing, preferences, statistics). See the docs for that on the
[GitHub page](https://github.com/Setono/ConsentBundle).

## Installing the plugin

Since the plugin is being provided through packagist.com you just have two steps to take before you can install the plugin:

**1. Add repository to composer.json**:
```shell
composer config repositories.private-packagist composer https://setono.repo.packagist.com/acme/
```
Remember to replace `acme` with the short name given to you.

**2. Add token to composer auth**:

```shell
composer config --global --auth http-basic.setono.repo.packagist.com token your_token
```

Remember to replace `your_token` with the token given to you.

Now you should be able to install the plugin using the normal `composer require` command:

```shell
composer require setono/sylius-consent-management-plugin
```

## Enabling the plugin

If you have Flex enabled the `composer require` will automatically add the bundles and the plugin to `bundles.php`.
If not you should manually add them:

```php
    Setono\SyliusConsentManagementPlugin\SetonoSyliusConsentManagementPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    
    // ...
    
    Setono\ClientIdBundle\SetonoClientIdBundle::class => ['all' => true],
    Setono\ConsentBundle\SetonoConsentBundle::class => ['all' => true],
```

{% hint style="info" %}
**NOTICE** that the order of the client id bundle and the consent bundle doesn't matter, but it's important that you
add the plugin _before_ the `SyliusGridBundle`.
{% endhint %}

## Add configuration file

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

{% hint style="info" %}
The `setono_sylius_consent_management.notify` option contains an array of email addresses to notify when new cookies are
discovered within your store. It's important that you send these notices to somebody who will take action on them so
that your store is compliant at all times.
{% endhint %}

## Include routes configuration

Create the file `config/routes/setono_sylius_cookie_consent.yaml` and add the following:

```yaml
# config/routes/setono_sylius_cookie_consent.yaml
setono_sylius_consent_management:
    resource: "@SetonoSyliusConsentManagementPlugin/Resources/config/routes.yaml"
```

{% hint style="info" %}
The plugin also provides a routes file for non localized stores. All you do is to use
`@SetonoSyliusConsentManagementPlugin/Resources/config/routes_no_locale.yaml` instead of
`@SetonoSyliusConsentManagementPlugin/Resources/config/routes.yaml`
{% endhint %}

## Add the javascript and CSS to webpack

In a Symfony recommended setup where you have an entry file with your imports, here is an example of how it will work
with the javascript and CSS included with this plugin:

```javascript
// assets/shop/entry.js
import { init } from 'vendor/setono/sylius-consent-management-plugin/src/Resources/assets/js/consent-widget';
import 'vendor/setono/sylius-consent-management-plugin/src/Resources/assets/css/consent-widget.css';
init();
```

## Output widget in HTML

Finally, you will output the widget in your application. You do this with the following `render` function:

```twig
{{ render(path('setono_sylius_consent_management_shop_partial_consent_widget')) }}
```
