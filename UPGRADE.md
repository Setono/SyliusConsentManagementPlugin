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
- The consent form (`ConsentEntryType`) no longer has a `url` field, because the URL is now always set server-side. A
  custom widget template that renders `form.url` must drop it.
- `/ajax/update-consent` answers requests from other sites, including other subdomains of the store, with a 403.
