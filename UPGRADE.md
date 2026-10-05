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
  - `/{_locale}/ajax/sample-cookies` answers 403 without the single-use token that
    `@SetonoSyliusConsentManagementPlugin/shop/javascripts/sample.html.twig` adds to its URL. If you override that
    template, pass the token like the plugin's template does:
    `path('setono_sylius_consent_management_shop_sample', { '_sample_token': token })`.
  - `?_sample=1` only forces sampling in debug mode. `?_sample=0` still turns sampling off everywhere.
  - Server-side sampling reads the cookie names from the `Cookie` header, so PHP no longer mangles them. Existing
    cookies with mangled names (dots and spaces turned into underscores, e.g. `ai_session_v1` for `ai_session.v1`)
    won't be seen again and go stale, while the real names are recorded as new cookies.
