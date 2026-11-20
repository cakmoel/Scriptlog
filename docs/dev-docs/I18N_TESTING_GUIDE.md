# i18n Testing Guide

**Project:** Blogware/Scriptlog CMS
**Version:** 2.1
**Last Updated:** November 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Test Environment Setup](#test-environment-setup)
3. [Running Tests](#running-tests)
4. [Unit Tests](#unit-tests)
5. [Integration Tests](#integration-tests)
6. [API Testing](#api-testing)
7. [Manual Testing Checklist](#manual-testing-checklist)
8. [RTL Testing](#rtl-testing)
9. [Performance Testing](#performance-testing)
10. [Troubleshooting](#troubleshooting)
11. [Continuous Integration](#continuous-integration)
12. [Appendix: Test Data](#appendix-test-data)

---

## Overview

This guide describes the i18n tests that currently exist in the repository and how to run them. It documents observed behavior, not planned coverage. For architecture details, see `docs/dev-docs/I18N_ARCHITECTURE.md`.

### Test files

Counts below reflect the current test files as of November 2026.

```text
tests/
├── unit/
│   ├── LocaleDetectorTest.php              17 tests (2 skipped)
│   ├── TranslationLoaderTest.php            9 tests
│   ├── I18nManagerTest.php                 29 tests (1 skipped)
│   ├── LocaleRouterTest.php                18 tests
│   ├── ThemeI18nTest.php                   37 tests
│   ├── LanguageSwitcherTest.php             9 tests
│   ├── TranslationCacheTest.php             2 tests
│   ├── NavigationI18nTest.php              23 tests
│   ├── AdminLocaleInitializationTest.php   13 tests
│   ├── AdminTranslationsTest.php           12 tests
│   ├── AdminLocalesCatalogTest.php          9 tests
│   └── InstallationTest.php                47 tests (3 install_i18n_data tests)
├── integration/
│   ├── LanguageSwitcherIntegrationTest.php 29 tests
│   ├── LanguageDaoIntegrationTest.php      14 tests (skipped in standard runs)
│   ├── TranslationServiceIntegrationTest.php 15 tests (skipped in standard runs)
│   └── PrivacyPolicyDaoIntegrationTest.php 16 tests (skipped in standard runs)
├── fixtures/
│   └── language_switcher_fixture.php
└── setup_test_db.php
```

The i18n-focused unit tests total **181 tests** (3 skipped). The language-switcher integration test adds **29 tests**. The three DB-backed DAO/service integration classes currently skip at class setup.

---

## Test Environment Setup

### Prerequisites

1. PHP 7.4+ with PHPUnit 9.5+ (current binary: `lib/vendor/bin/phpunit`, PHPUnit 9.6.38)
2. MySQL/MariaDB for integration tests that use a database
3. Composer dependencies installed

### Database setup

```bash
mysql -u blogwareuser -p -e "CREATE DATABASE IF NOT EXISTS blogware_test"
php tests/setup_test_db.php
```

`tests/setup_test_db.php` creates the tables and seeds **six** languages: `en`, `ar`, `zh`, `fr`, `es`, `id`. It does not seed `ru` and does not call `install_i18n_data()`. Translation rows are not seeded by this script.

`install_i18n_data()` is an installer function, not the test-DB setup path. During a real installation it seeds seven languages and 165 keys per language (1,155 rows), plus privacy policies. Its behavior is covered behaviorally in `tests/unit/InstallationTest.php`.

### Translation sources in tests

| Source | Used by | Notes |
|--------|---------|-------|
| `tbl_translations` | `I18nManager`, `TranslationLoader`, `admin_translate()` | Database source for core/admin consumers |
| `public/files/cache/translations/{locale}.json` | `TranslationLoader` | Generated flat key/value cache; runtime artifact |
| `public/themes/blog/lang/{locale}.json` | Theme `t()` | Source files for frontend templates; fallback for core loader |
| Hardcoded arrays | `admin_translate_fallback()` | Used when DB rows are unavailable |

The blog theme ships JSON files for `en`, `ar`, `zh`, `fr`, `ru`, `es`, and `id`.

### Verify the test database connection

```bash
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=blogware_test', 'blogwareuser', 'userblogware');
echo 'Database connection successful!';
"
```

These credentials are hardcoded in `tests/setup_test_db.php`; they are not read from `config.php`.

---

## Running Tests

Run commands from `/var/www/blogware/public_html`.

### Run the i18n unit tests

PHPUnit 9 does not execute multiple explicit file paths in one invocation. Run each file separately:

```bash
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/TranslationLoaderTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/I18nManagerTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/LocaleRouterTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/ThemeI18nTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/LanguageSwitcherTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/TranslationCacheTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/NavigationI18nTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/AdminLocaleInitializationTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/AdminTranslationsTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/AdminLocalesCatalogTest.php
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/InstallationTest.php --filter testInstallI18nData
```

### Run the active i18n integration test

```bash
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/integration/LanguageSwitcherIntegrationTest.php
```

### Run the whole suite

```bash
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit --configuration phpunit.xml
```

The suite rewrites files under `public/files/cache/translations/`. After a full run, check `git status` and revert incidental cache changes unless they were intentional:

```bash
git checkout -- public/files/cache/translations/
```

### Run a specific method

```bash
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php --filter testIsValidLocaleWithValidLocale
```

### Coverage

The coverage command below runs the **whole** unit suite; it is not i18n-scoped:

```bash
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit --configuration phpunit.xml --coverage-html coverage/full/
```

### Current pass summary

| Test file | Tests | Notes |
|-----------|------:|-------|
| `LocaleDetectorTest.php` | 17 | 2 skipped (cookie/CLI-related paths) |
| `TranslationLoaderTest.php` | 9 | Includes interpolation and cache logic checks |
| `I18nManagerTest.php` | 29 | 1 skipped (`testSetLocaleWithValidLocale`) |
| `LocaleRouterTest.php` | 18 | Prefix extraction and URL building |
| `ThemeI18nTest.php` | 37 | Theme JSON keys, interpolation, privacy/404 keys |
| `LanguageSwitcherTest.php` | 9 | Helper-level URL/name generation |
| `TranslationCacheTest.php` | 2 | Single loader; service write paths invalidate cache |
| `NavigationI18nTest.php` | 23 | Locale-aware navigation helpers |
| `AdminLocaleInitializationTest.php` | 13 | Admin default locale seeding |
| `AdminTranslationsTest.php` | 12 | Admin translation helpers |
| `AdminLocalesCatalogTest.php` | 9 | Admin locale catalog |
| `InstallationTest.php` (i18n methods) | 3 | Installer transaction, timeout, and 165-key behavior |
| `LanguageSwitcherIntegrationTest.php` | 29 | Fixture-based full switch flow |

---

## Unit Tests

Method lists below are grouped by responsibility. The test files are the source of truth for exact method names.

### LocaleDetectorTest

- Validates supported/unsupported locale codes.
- Covers available/default locale accessors and setters.
- Covers browser `Accept-Language` detection.
- Two URL/session tests are skipped in CLI runs because they depend on cookie behavior.

### TranslationLoaderTest

- Covers cache directory/path setup, enable/disable state, interpolation, expiry, and in-memory caching.
- Some cache-structure assertions use local arrays rather than the production `TranslationLoader::saveToCache()` output. Treat `lib/core/TranslationLoader.php` as the authority for the on-disk shape (a flat `{key: value}` map).

### I18nManagerTest

- Covers singleton access, locale getters/setters, translation fallback, RTL helpers, URL building, and getters for loader/router/detector.
- `testSetLocaleWithValidLocale` is skipped in CLI runs.

### LocaleRouterTest

- Covers locale extraction/stripping from request paths, prefix detection, route matching, and direct URL building.
- `LocaleRouter::buildUrl()` always prefixes the supplied locale; this differs from the theme's `locale_url()`.

### ThemeI18nTest

- Covers `detect_browser_locale()`, `t()`, `load_theme_translations()`, and `available_locales()`.
- Asserts key consistency across all seven theme JSON files.
- Includes cookie-consent, privacy, and 404 key checks.

### LanguageSwitcherTest

- Covers `get_language_name()`, URL generation patterns, redirect encoding, and all supported locale codes.

### TranslationCacheTest

- Asserts one `TranslationLoader` instance per request.
- Statically checks that `TranslationService::createTranslation()`, `updateTranslation()`, `deleteTranslation()`, and `importFromArray()` invalidate the file cache.
- Snapshots and restores cache files in `setUp()`/`tearDown()`.

### NavigationI18nTest, AdminLocaleInitializationTest, AdminTranslationsTest, AdminLocalesCatalogTest

- Cover locale-aware navigation data, admin locale initialization from `lang_default`, admin translation helpers, and the admin locale catalog.

### InstallationTest (i18n subset)

- `testInstallI18nDataSetsTimeLimit`: verifies the bulk-insert timeout guard.
- `testInstallI18nDataTransactionStructure`: verifies `begin_transaction → try → foreach → commit → catch → rollback → throw`.
- `testInstallI18nDataInsertsEveryUniqueTranslationOncePerLanguage`: captures the installer's SQL with a mysqli-like fake and asserts seven locales, 165 unique keys per locale, identical key sets, representative contexts, and one commit with no rollback.

---

## Integration Tests

### Active: LanguageSwitcherIntegrationTest

This test uses `tests/fixtures/language_switcher_fixture.php`. It **does not include or execute `lib/main.php`**; it models the switch flow in a fixture. Production `lib/main.php` is stricter: it accepts only scalar two-letter codes, uses strict allow-list checks, and rejects open-redirect targets.

Covered areas:

- `?switch-lang=` handling and session/cookie persistence.
- Redirect construction, including preservation of the original path/query and an explicit `redirect` parameter.
- Locale-filtered posts, topics, and menus when the test DB is available.
- RTL detection for Arabic and LTR for other bundled locales.
- Invalid, malformed, empty, uppercase, and sequential switch inputs.

Representative current method names:

- `testSwitchLangSetsSessionAndReturnsRedirect`
- `testRedirectUrlPreservesOriginalPathAndQuery`
- `testCookieFallbackWhenSessionExpires`
- `testArabicIsRtlInDatabase`
- `testUpperCaseLocaleIsNormalizedToLowercase`

### Currently skipped DB-backed classes

`LanguageDaoIntegrationTest`, `TranslationServiceIntegrationTest`, and `PrivacyPolicyDaoIntegrationTest` contain 14, 15, and 16 test methods respectively, but skip during standard runs. The skip reason is that `tests/bootstrap.php` does not register the `dbc` object required by DAO constructors:

```text
Cannot connect to test database: Database connection (dbc) is not set in the Registry
```

They are useful as executable specifications, but they are not active DB coverage in the standard suite. Do not describe them as passing CRUD tests unless the test harness is updated to register `dbc`.

---

## API Testing

The i18n REST API is registered under `/api/v1/`.

### List languages

```bash
curl -X GET "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json"
```

### Create a language

Write endpoints require authentication and administrator permission. Use an API key or bearer token:

```bash
curl -X POST "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "lang_code": "fr",
    "lang_name": "French",
    "lang_native": "Français"
  }'
```

Alternative authentication header:

```bash
-H "Authorization: Bearer YOUR_TOKEN"
```

`scriptlog_auth` is a browser auth cookie, not an API authentication header.

### Translation endpoints

```bash
curl -X GET "http://localhost/api/v1/translations/en" \
  -H "Content-Type: application/json"

curl -X GET "http://localhost/api/v1/translations/en/export" \
  -H "Content-Type: application/json"
```

### OpenAPI specification

Available locations:

- `docs/dev-docs/API_OPENAPI.json`
- `docs/html/API_OPENAPI.json`
- `openapi.json`
- Runtime endpoint: `GET /api/v1/openapi.json`

There is no checked-in `tests/scripts/api-test.sh`.

---

## Manual Testing Checklist

### Language management

- [ ] Add a new language
- [ ] Edit language details
- [ ] Set language as default
- [ ] Delete a non-default language
- [ ] Attempt to delete the default language (should fail)
- [ ] Toggle language active/inactive
- [ ] Sort languages

### Translation management

- [ ] View translation list
- [ ] Filter by context
- [ ] Search translations
- [ ] Create, edit, and delete a translation
- [ ] Export translations to JSON
- [ ] Import translations from JSON
- [ ] Regenerate cache

### Locale detection and routing

- [x] Locale prefix `/en/blog` resolves correctly
- [x] Locale prefix `/es/blog` resolves correctly
- [x] Session-stored locale persists
- [x] Cookie-stored locale persists
- [x] Browser language auto-detection works
- [x] Default locale fallback works

### Theme integration

- [x] `t()` translates theme JSON keys (37 tests in `ThemeI18nTest`)
- [x] `locale_url()` respects permalink and prefix settings
- [x] `get_locale()` returns the current frontend locale
- [x] `is_rtl()` and `get_html_dir()` return correct values
- [x] `language_switcher()` renders the available locales

---

## RTL Testing

### Bundled RTL locale

Only **Arabic (`ar`)** is RTL in the bundled locale set.

| Language | Code | Direction | Bundled |
|----------|------|-----------|---------|
| Arabic | ar | RTL | Yes |
| Hebrew | he | RTL | No |
| Farsi | fa | RTL | No |
| Urdu | ur | RTL | No |

Hebrew, Farsi, and Urdu are only relevant if added as custom DB languages with matching direction and translation files; they are not shipped with the blog theme.

### RTL browser checklist

1. Set `ar` as the active frontend locale.
2. Navigate to the home page.
3. Verify:
   - [ ] HTML has `dir="rtl"`
   - [ ] `rtl.css` is loaded from `public/themes/blog/assets/css/`
   - [ ] `rtl.js` is loaded from `public/themes/blog/assets/js/`
   - [ ] Text is right-aligned
   - [ ] Navigation aligns right
   - [ ] Icons flip correctly
   - [ ] Dropdowns open left

### RTL CSS checklist

- [ ] Margins and paddings are mirrored
- [ ] Forms have correct direction
- [ ] Modal close button is on the left
- [ ] Browser console shows no errors

---

## Performance Testing

### Translation lookup benchmark

```php
<?php
$start = microtime(true);

for ($i = 0; $i < 1000; $i++) {
    $i18n = I18nManager::getInstance();
    $i18n->t('header.nav.home');
}

$elapsed = microtime(true) - $start;
echo "1000 translations: {$elapsed} seconds\n";
echo "Average per translation: " . (($elapsed / 1000) * 1000) . " ms\n";
```

This benchmark exercises the core `I18nManager` path (DB/generated cache → JSON → key), not the theme's global `t()` helper.

### Indicative targets

| Operation | Target | Acceptable |
|-----------|--------|------------|
| Cached translation lookup | < 1ms | < 5ms |
| Full locale load | < 50ms | < 200ms |
| Page with 50 translations | < 100ms | < 500ms |
| Cache regeneration | < 500ms | < 2000ms |

### Cache warm-up check

```bash
rm -f public/files/cache/translations/*.json
time curl -s "http://localhost/en/" > /dev/null
time curl -s "http://localhost/en/" > /dev/null
```

---

## Troubleshooting

### Database connection failed

The test DB credentials are in `tests/setup_test_db.php`, not `config.php`:

```php
new PDO('mysql:host=localhost;dbname=blogware_test', 'blogwareuser', 'userblogware');
```

Check that MySQL is running and that `blogware_test` exists.

### DAO class not found

1. Verify Composer autoload: `composer dump-autoload`
2. Confirm the class file exists under `lib/dao/`
3. For DB-backed integration tests, remember that `dbc` is not registered by `tests/bootstrap.php`

### Translation cache not updating

Clear the generated cache and invalidate one locale through the loader:

```bash
rm -f public/files/cache/translations/*.json
php -r "
require 'lib/main.php';
\$loader = new TranslationLoader();
\$loader->invalidate('en');
"
```

`TranslationService` write methods should also call `invalidate()`; `TranslationCacheTest` checks this statically.

### RTL not working

1. Confirm the locale is `ar` and `lang_direction` is `rtl`
2. Confirm `public/themes/blog/assets/css/rtl.css` exists
3. Confirm `public/themes/blog/assets/js/rtl.js` exists
4. Confirm the rendered HTML contains `dir="rtl"`
5. Check the browser console for errors

### Log files

Current log files include:

- `public/log/error.log`
- `public/log/exceptions.log`
- `public/log/hacklog.log`
- `public/log/session.log`

There is no `public/log/debug.log`.

---

## Continuous Integration

Example workflow. Run each test file in its own PHPUnit invocation; passing several paths to PHPUnit 9 only runs the first one.

```yaml
name: i18n Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - run: composer install --no-interaction --prefer-dist
      - run: php tests/setup_test_db.php
      - name: Run i18n tests
        run: |
          for test in \
            tests/unit/LocaleDetectorTest.php \
            tests/unit/TranslationLoaderTest.php \
            tests/unit/I18nManagerTest.php \
            tests/unit/LocaleRouterTest.php \
            tests/unit/ThemeI18nTest.php \
            tests/unit/LanguageSwitcherTest.php \
            tests/unit/TranslationCacheTest.php \
            tests/unit/NavigationI18nTest.php \
            tests/unit/AdminLocaleInitializationTest.php \
            tests/unit/AdminTranslationsTest.php \
            tests/unit/AdminLocalesCatalogTest.php \
            tests/integration/LanguageSwitcherIntegrationTest.php
          do
            php -d error_reporting='E_ALL' lib/vendor/bin/phpunit "$test" || exit 1
          done
          php -d error_reporting='E_ALL' lib/vendor/bin/phpunit tests/unit/InstallationTest.php --filter testInstallI18nData
      - name: Restore generated translation cache
        if: always()
        run: git checkout -- public/files/cache/translations/ || true
```

---

## Appendix: Test Data

### Sample languages

Illustrative rows for manual DB work. The installer's authoritative locale list is in `install/include/setup.php`:

```sql
INSERT INTO tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction) VALUES
('en', 'English', 'English', 'en_US', 'ltr'),
('ar', 'Arabic', 'العربية', 'ar_SA', 'rtl'),
('zh', 'Chinese', '中文', 'zh_CN', 'ltr'),
('fr', 'French', 'Français', 'fr_FR', 'ltr'),
('es', 'Spanish', 'Español', 'es_ES', 'ltr'),
('id', 'Indonesian', 'Bahasa Indonesia', 'id_ID', 'ltr');
```

### Sample translations

```sql
INSERT INTO tbl_translations (lang_id, translation_key, translation_value, translation_context) VALUES
(1, 'header.nav.home', 'Home', 'header'),
(1, 'header.nav.blog', 'Blog', 'header'),
(1, 'header.nav.search', 'Search', 'header'),
(1, 'footer.copyright', 'All rights reserved', 'footer'),
(1, 'form.submit', 'Submit', 'form'),
(1, 'form.cancel', 'Cancel', 'form'),
(1, 'error.not_found', 'Page not found', 'error'),
(1, 'pagination.previous', 'Previous', 'pagination'),
(1, 'pagination.next', 'Next', 'pagination');
```

For the complete installer context list, see `docs/dev-docs/I18N_ARCHITECTURE.md` §4. For theme keys, see `public/themes/blog/lang/en.json`.
