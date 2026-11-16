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
  - Names that aren't valid cookie names (RFC 6265 tokens, e.g. names with spaces) are no longer recorded, and at most
    50 cookies are recorded per request.
  - `/{_locale}/ajax/sample-cookies` answers 403 without a valid sample token, which
    `@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig` adds to its URL. A token expires after 10
    minutes and is meant to be used once. That's best-effort: the used tokens are kept in `cache.app` without a lock,
    so parallel requests can reuse a token, and with a cache per server a token can be used once per server. If you
    override that template, post to its new `sample_url` variable, which includes the token, instead of generating the
    URL with `path()`.
  - `?_sample=1` only forces sampling in debug mode. `?_sample=0` still turns sampling off everywhere.
  - BC break: the first constructor argument of `SampleController`, `SaveCookiesSubscriber` and
    `SampleCookiesServerSideSubscriber` changed from `CookieRepositoryInterface` to `CookieRecorderInterface`. If you
    redefine any of these services, pass the `CookieRecorderInterface` service as the first argument.
- `CookieRepositoryInterface::findOneByName()` is deprecated, because the plugin doesn't use it anymore, and will be
  removed in the next major version. Use `findOneBy(['name' => $name])` instead.
