# UPGRADE

The plugin doesn't ship Doctrine migrations. Entries marked **Mapping change** change the Doctrine mapping, so generate
and review a migration after upgrading past one of them:

```shell
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Unreleased

- **Mapping change**: Deleting a service no longer deletes the cookies assigned to it. The cookies become unassigned
  instead (`setono_sylius_consent_management__cookie.service_id` is now `ON DELETE SET NULL`).
- Sampled cookies no longer store the query string or fragment of the URL they were found on, and the URL is cut to
  255 characters. For client-side samples, the URL is only stored when the Referer is on the shop's own host.
