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
- Sampled cookies and consent entries no longer store the query string or fragment of the page URL, since it may contain
  the visitor's tracking ids. Only http(s) URLs on the shop's own host are stored; otherwise a cookie gets no URL and a
  consent entry an empty one. Cookie URLs are cut to 255 characters, and crawled cookie URLs keep their query string.
