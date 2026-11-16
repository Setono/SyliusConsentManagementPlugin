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
- Cookie sampling changed:
  - Server-side sampling records the cookies on `kernel.terminate`, after the response has been sent, instead of on
    `kernel.request`.
  - All sources record cookies through the new `CookieRecorderInterface`, which saves them with an entity manager of
    its own. Doctrine listeners for cookies get that entity manager in their event arguments, not the default one.
  - BC break: the first constructor argument of `SampleController`, `SaveCookiesSubscriber` and
    `SampleCookiesServerSideSubscriber` changed from `CookieRepositoryInterface` to `CookieRecorderInterface`. If you
    redefine any of these services, pass the `CookieRecorderInterface` service as the first argument.
- `CookieRepositoryInterface::findOneByName()` is deprecated, because the plugin doesn't use it anymore, and will be
  removed in the next major version. Use `findOneBy(['name' => $name])` instead.
