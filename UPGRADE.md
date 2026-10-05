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
- `CrawlerInterface::start()` returns a `CrawlResult` with the number of crawled and failed URLs instead of `void`.
  Update your implementation if you replaced the crawler. The `setono:sylius-consent-management:crawl` command now
  fails when a URL fails to load.
