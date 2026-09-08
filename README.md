# CMS Observer Client (TYPO3)

Reports this site's TYPO3 core version, PHP/DB versions, and installed
extensions to a central [CMS Observer](../../) instance.

## Install

```
composer require portrino/cms-observer-client
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

## Run

```
vendor/bin/typo3 cms-observer:push
```

Schedule it with TYPO3's built-in Scheduler using the **Execute console
commands** task type (command: `cms-observer:push`), or via system cron.
