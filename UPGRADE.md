# UPGRADE

The plugin doesn't ship Doctrine migrations. After upgrading, generate and review a migration for the mapping changes
listed below:

```shell
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Unreleased

- Deleting a service no longer deletes the cookies assigned to it. The cookies become unassigned instead
  (`setono_sylius_consent_management__cookie.service_id` is now `ON DELETE SET NULL`).
- The `ConsentManager` constructor no longer loads the widget or dispatches the consent events; its new `init()` method
  does. If you override `shop/resources.html.twig`, call `window.sscmManager.init()` after creating the manager.
