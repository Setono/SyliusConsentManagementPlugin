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
- Granting consent through the query string (`?_consent=1`, `?_consent[marketing]=1`) now only works in debug mode or
  when the override is signed. Denying consent (`?_consent=0`) still works. If you use `?_consent=1` for QA or tag
  audits in production, get a signed URL with
  `php bin/console setono:sylius-consent-management:sign-consent-url https://example.com/` instead (see `--help` for
  granting single categories and setting the expiry).
