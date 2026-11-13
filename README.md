# CMS Observer Client (TYPO3)

Reports this site's TYPO3 core version, PHP/DB versions, and installed
extensions to a central [CMS Observer](../../) instance.

Supports TYPO3 12.4, 13.4 and 14.3 on PHP 8.2+.

## Install

```
composer require portrino/cms-observer-client-typo3
```

## Configure

1. On the Observer, register this site and copy the printed token:
   ```
   php bin/console app:site:create "<name>" typo3 "<site-url>"
   ```
2. In the TYPO3 backend, go to **Admin Tools > Settings > Extension
   Configuration > cms_observer_client** and set:
   - `apiUrl` – the Observer's base URL (no trailing slash)
   - `token` – the token from step 1

   Alternatively set the environment variables `CMS_OBSERVER_API_URL` and
   `CMS_OBSERVER_TOKEN`. They take precedence over the extension
   configuration, so a shared `settings.php` can serve several environments.

## Run

```
vendor/bin/typo3 cms-observer:push
```

Schedule it with TYPO3's built-in Scheduler using the **Execute console
commands** task type (command: `cms-observer:push`), or via system cron.

To check what would be sent without sending it:

```
vendor/bin/typo3 cms-observer:push --dry-run
```

## Development

Uses DDEV with one TYPO3 instance per supported version (`.Build/12`,
`.Build/13`, `.Build/14`):

```
ddev start
ddev composer ci:test     # unit tests
ddev composer ci          # composer checks + static analysis
ddev install all          # set up the TYPO3 instances
ddev 14 typo3 cms-observer:push --dry-run
```

Keep the version in `ext_emconf.php` and `extra.typo3/cms.version` in
`composer.json` in sync with the release tag.
