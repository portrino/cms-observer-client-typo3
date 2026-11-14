# TYPO3 Extension `cms_observer_client`

[![Latest Stable Version](https://poser.pugx.org/portrino/cms-observer-client-typo3/v/stable)](https://packagist.org/packages/portrino/cms-observer-client-typo3)
[![TYPO3 12.4 13.4 14.3](https://img.shields.io/badge/TYPO3-12.4_|_13.4_|_14.3-orange.svg?logo=typo3)](https://get.typo3.org/)
[![PHP 8.2 - 8.5](https://img.shields.io/badge/PHP-8.2_--_8.5-4f5b93.svg?logo=php)](https://www.php.net/releases/)
[![Total Downloads](https://poser.pugx.org/portrino/cms-observer-client-typo3/downloads)](https://packagist.org/packages/portrino/cms-observer-client-typo3)
[![Monthly Downloads](https://poser.pugx.org/portrino/cms-observer-client-typo3/d/monthly)](https://packagist.org/packages/portrino/cms-observer-client-typo3)
[![License](https://poser.pugx.org/portrino/cms-observer-client-typo3/license)](https://packagist.org/packages/portrino/cms-observer-client-typo3)
[![CI](https://github.com/portrino/cms-observer-client-typo3/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/portrino/cms-observer-client-typo3/actions/workflows/ci.yml)

This extension provides a client to report a site's TYPO3 core version, PHP + DB versions, and installed extension 
versions to a central [CMS Observer](https://github.com/portrino/cms-oberserver) instance.

> [!IMPORTANT]
> Without a central [CMS Observer](https://github.com/portrino/cms-oberserver) instance this extension is somewhat 
> useless. So setup your central CMS Observer instance first and then connect any number of TYPO3 instances by using 
> this client.

## 🔥 Installation

### Composer

The **recommended** way to install the extension is using [composer](https://getcomposer.org/).

Run the following command within your Composer based TYPO3 project:

```bash
composer require portrino/cms-observer-client-typo3
```

### TER

* Open the TYPO3 Extension Manager, search for `cms_observer_client` and install the extension.

or 

* Download the zip file from [TYPO3 extension repository (TER)](https://extensions.typo3.org/extension/cms_observer_client).

## 🔧 Configuration

1. On the Observer, register a new site (using command line or web interface) and copy the printed token:
   ```
   php bin/console app:site:create "<name>" typo3 "<site-url>"
   ```
2. In the TYPO3 backend, go to **Admin Tools > Settings > Extension
   Configuration > cms_observer_client** and set:
   - `apiUrl` – the Observer's base URL, must use `https://` (no trailing slash)
   - `token` – the token from step 1

   Alternatively set the environment variables `CMS_OBSERVER_API_URL` and
   `CMS_OBSERVER_TOKEN`. They take precedence over the extension
   configuration, so a shared `settings.php` can serve several environments.

> [!TIP]
> Set the token per environment via `CMS_OBSERVER_TOKEN`, and give staging its
> own site on the Observer (or no token at all). Otherwise a staging copy, e.g.
> one whose scheduler task came along with a production database dump, reports
> as production. As a safety net the client sends the domains of all site
> configurations, and the Observer rejects reports whose domains don't match
> the site's URL. That only helps if staging has its own domain (e.g. via
> `baseVariants`).

## 🚀 Run

```bash
vendor/bin/typo3 cms-observer:push
```

Schedule it with TYPO3's built-in Scheduler using the **Execute console
commands** task type (command: `cms-observer:push`), or via system cron.

To check what would be sent without sending it:

```bash
vendor/bin/typo3 cms-observer:push --dry-run
```

## 💎 Credits

This project is highly inspired by the [t3monitoring_client](https://github.com/georgringer/t3monitoring_client) and
the SYSSY monitoring with its [syssy-typo3-extension](https://gitlab.com/syssy-typo3-extension/syssy).

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).
