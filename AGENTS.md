# AGENTS.md

TYPO3 extension `cms_observer_client` (Composer: `portrino/cms-observer-client-typo3`).
It collects the site's TYPO3 core, PHP and DB versions plus the installed
third-party extensions and pushes them to a CMS Observer instance
(a separate Symfony application, its own repository).

## Support matrix

TYPO3 12.4, 13.4 and 14.3 on PHP 8.2+. Every change must work on all of them:

- No syntax newer than PHP 8.2: e.g. no `new Foo()->bar()` without parentheses
  (8.4), no typed class constants (8.3). The Rector config pins `PHP_82` and
  `UP_TO_TYPO3_12` for that reason; don't raise them while v12 is supported.
- Only public TYPO3 API. Check the class docblock for `@internal` in the core
  sources of each version under `.Build/<version>/vendor/typo3/`.
  `ListUtility` (EXT:extensionmanager) is `@internal` in all three versions,
  which is why the extension list comes from `PackageManager`.
- `composer.json` pins `config.platform.php` to `8.2.0`, so the dev lock file
  resolves to packages that run on the lowest supported PHP (the DDEV hook
  installs with `--ignore-platform-reqs`, which would otherwise pull PHP 8.4-only
  Symfony 8 packages and break the tools once the autoloader is regenerated).

## How it works

- `PushReportCommand` (`cms-observer:push`, option `--dry-run` prints the JSON
  instead of sending it) → `ReportBuilder` → `EnvironmentService` (versions,
  application context) and `ExtensionInventoryService` (extensions) →
  `ObserverApiClient`.
- Extension versions: `Composer\InstalledVersions::getPrettyVersion()` first,
  package metadata (`ext_emconf.php` / composer.json) only as fallback. In
  Composer mode `ext_emconf.php` is often stale, and TYPO3 v14 reports
  `1.0.0+no-version-set` when composer.json has no version. v12/v13 can report
  an empty string, which we send as `0.0.0` (v14's `MetaData::setVersion()`
  rejects an empty string, so tests can't build that case on v14).
- System extensions (`typo3-cms-framework`) are skipped, only third-party
  extensions are reported.
- Configuration: extension settings `apiUrl` and `token`, overridden by the
  environment variables `CMS_OBSERVER_API_URL` and `CMS_OBSERVER_TOKEN`
  (so a committed `settings.php` can serve several environments). If the
  extension has no configuration at all, `ExtensionConfiguration::get()`
  throws; the client then falls back to the environment variables.
- HTTP timeout is set explicitly (10 s): TYPO3's default is `0` (wait forever),
  which would hang the scheduler when the Observer is unreachable.

## API contract with the Observer

`POST {apiUrl}/api/v1/sites/report`, `Authorization: Bearer <token>`, JSON body:

```json
{
  "coreVersion": "13.4.0", "phpVersion": "8.3.0", "dbVersion": "11.8.0",
  "clientVersion": "1.0.0", "applicationContext": "Production",
  "extensions": [
    {"name": "news", "title": "News", "version": "12.0.0", "active": true, "composerName": "georgringer/news"}
  ]
}
```

Success is HTTP `202`. Sites update the client late or never, so the Observer
must keep accepting older payloads: add new fields as optional, never rename or
remove one. Coordinate payload changes with the Observer repository (its
`SiteReportRequest` DTO).

## Workflow

DDEV (typo3-multi-version-extension add-on) with one Composer instance per
TYPO3 version in `.Build/12`, `.Build/13`, `.Build/14`; tools and the
extension's own dev dependencies live in `.Build/vendor`.

- `ddev start`, `ddev install all` (set up the instances),
  `ddev 14 typo3 cms-observer:push --dry-run`, `ddev all <cmd>`.
- `ddev composer ci:test`: unit tests (`Build/phpunit.xml.dist`).
- `ddev composer ci`: composer checks (normalize, audit, dependency analyser,
  PSR-4) and static checks (Fractor, JSON/YAML lint, PHP-CS-Fixer with the
  TYPO3 coding standard, PHPStan level 8 with strict rules and cognitive
  complexity limits). `ddev composer fix:php:cs` fixes code style.
- PHPStan: fix the cause, don't suppress or baseline errors.
- Composer blocks all public TYPO3 12.4 releases because of security
  advisories (fixes ship via ELTS only). To test against v12 locally, set
  `COMPOSER_NO_SECURITY_BLOCKING=1` for that one command; don't disable the
  advisory policy in composer.json.
- Overrides for the add-on (extension key, TYPO3 versions) belong in
  `.ddev/docker-compose.zz-project.yaml`, not in add-on-managed files.

## Testing

Unit tests in `Tests/Unit` (PHPUnit 11), TYPO3 services are stubbed. Every
change needs a test that calls the code the way a caller would. `Tests/Acceptance`
only holds the add-on's site package fixture.

## Releases

- SemVer. Keep `version` in `ext_emconf.php` and `extra.typo3/cms.version` in
  `composer.json` in sync with the tag. Don't put a top-level `version` into
  composer.json, Packagist takes it from the tag.
- `ext_emconf.php` is still needed for v12/v13 classic mode; v14 deprecates it.
  The `ExtEmConfRector` constraints in `Build/rector.php` must match the
  support matrix above.
- Release targets: Packagist (`portrino/cms-observer-client-typo3`), TER
  (extension key `cms_observer_client`). `composer prepare-release` strips the
  dev files.

## Git

Commit subject prefixes `[FEATURE]`, `[TASK]`, `[BUGFIX]`, `[CLEANUP]`
(changes without functional effect), following the TYPO3 commit message rules
(https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html):
imperative subject, capitalized after the prefix, under 52 chars if possible,
never over 72; body wrapped at 72 explaining what and why. TYPO3-Core-only
footers (`Resolves:`, `Releases:`, `Change-Id:`) don't apply.
