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
- `sscm_script_tag()` now HTML-escapes the URL it renders. If a template already escapes the URL, e.g. with `|e`,
  remove that, otherwise the URL is escaped twice and the browser requests `?id=G-1&amp;l=dataLayer` instead of
  `?id=G-1&l=dataLayer`.
- Widget layout values may only contain letters, numbers, spaces and the characters `# % . , ( ) + - * /`. Stored
  values with anything else, e.g. `#fff !important` or quotes, are no longer rendered in the shop, and the widget
  config form refuses to save until they're changed.
