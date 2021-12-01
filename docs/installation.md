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
composer req setono/sylius-consent-management-plugin
```
