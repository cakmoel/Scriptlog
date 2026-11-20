# i18n (Internationalization & Localization) Architecture

**Project:** Blogware/Scriptlog CMS
**Version:** 2.1
**Last Updated:** November 2026
**Status:** Current implementation

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Database Schema](#database-schema)
4. [Installation & Setup](#installation--setup)
5. [Locale Routing and URL Generation](#locale-routing-and-url-generation)
6. [Translation Storage, Loading, and Fallback](#translation-storage-loading-and-fallback)
7. [Privacy Policy Implementation](#privacy-policy-implementation)
8. [Theme Integration](#theme-integration)
   - [Frontend Language Switcher](#frontend-language-switcher)
9. [Translation Keys](#translation-keys)
10. [Files Reference](#files-reference)
11. [Admin Language Switcher](#admin-language-switcher)
12. [REST API](#rest-api)
13. [Verification Checklist](#verification-checklist)

---

## 1. Executive Summary

This document describes the i18n implementation currently in the repository. It covers locale detection, URL handling, translation storage and fallback, installer seed data, theme helpers, privacy policies, the admin switcher, and the REST API.

| Area | Current implementation |
|------|------------------------|
| **Scope** | UI strings, locale columns on posts/topics/menus, theme language files, privacy policies, and admin translations |
| **Supported locales** | `en`, `ar`, `zh`, `fr`, `ru`, `es`, `id`; `ar` is RTL |
| **Translation storage** | `tbl_translations` is the database source; `TranslationLoader` writes a flat generated JSON cache under `public/files/cache/translations/`; theme JSON files under `public/themes/{theme}/lang/` are the fallback source |
| **Frontend `t()`** | Global theme helper in `functions-i18n.php`; reads the active theme's JSON file, falls back to English JSON, then to the key |
| **Core `I18nManager::t()`** | Separate facade that reads loaded database/cache translations, falls back to theme JSON, then to the key |
| **URL prefixes** | `Dispatcher` accepts and strips `/{locale}/...` paths in SEO mode; theme URL generation adds a prefix only when permalinks and `lang_prefix_required` are enabled |
| **Default locale URL** | Theme helpers do not prefix the default locale. `I18nManager::url()`/`LocaleRouter::buildUrl()` prefix unconditionally when called directly |
| **Admin/frontend separation** | Admin uses `admin_locale` and `?lang=`; frontend uses `scriptlog_locale` and `?switch-lang=` |

---

## 2. Architecture Overview

### Request flow

1. `Dispatcher::handleSeoFriendlyUrl()` inspects the request path.
2. When a locale prefix is present, `LocaleRouter::extractLocale()` validates it, `I18nManager::setLocale()` selects it, and `LocaleRouter::stripLocalePrefix()` restores the normal route path.
3. `Bootstrap::defineRoutingRules()` supplies the base routes only. There are no separate `*_with_locale` routes; prefixed paths are handled generically before normal route matching.
4. `LocaleDetector` resolves the active locale from validated session/cookie values, browser `Accept-Language`, and configured defaults.
5. `TranslationLoader` loads database rows or the generated JSON cache for core consumers. The blog theme's global `t()` helper loads theme JSON directly.
6. Templates render through the theme helpers (`get_locale()`, `t()`, `locale_url()`, `is_rtl()`, `get_html_dir()`).

There is no generic `ContentFilter` class. Locale-aware navigation filtering is implemented in `lib/utility/theme-navigation.php`, and content queries use the `*_locale` columns where appropriate.

### Fallback priority

The project has two distinct translation lookups:

| Consumer | Lookup order | Placeholder syntax |
|----------|--------------|--------------------|
| Theme `t()` | Active theme `lang/{locale}.json` → `lang/en.json` → key | `%name%` |
| `I18nManager::t()` | Loaded DB/generated-cache value → theme `lang/{locale}.json` → key | `:name` |
| `admin_translate()` | `tbl_translations` for the admin locale → hardcoded fallback table → key | See `admin_t()` for parameters |

`TranslationLoader` falls back to theme JSON when DAOs are unavailable or a query fails, so the frontend still renders strings when the database is not reachable.

---

## 3. Database Schema

### Existing tables

#### `tbl_languages`

Defined in `install/include/dbtable.php`:

```sql
CREATE TABLE IF NOT EXISTS {$prefix}tbl_languages (
    ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    lang_code VARCHAR(10) NOT NULL,
    lang_name VARCHAR(50) NOT NULL,
    lang_native VARCHAR(50) NOT NULL,
    lang_locale VARCHAR(10) DEFAULT NULL,
    lang_direction ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    lang_sort INT(11) NOT NULL DEFAULT 0,
    lang_is_default TINYINT(1) NOT NULL DEFAULT 0,
    lang_is_active TINYINT(1) NOT NULL DEFAULT 1,
    lang_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY lang_code (lang_code)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### `tbl_translations`

`translation_context` is used for grouping and admin filtering; lookups are keyed by `translation_key`. The current installer schema has no foreign key on `lang_id`:

```sql
CREATE TABLE IF NOT EXISTS {$prefix}tbl_translations (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    lang_id INT(11) UNSIGNED NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    translation_context VARCHAR(100) DEFAULT NULL,
    translation_plurals VARCHAR(255) DEFAULT NULL,
    is_html TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY lang_key (lang_id, translation_key),
    KEY lang_id (lang_id),
    KEY translation_key (translation_key(191))
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Privacy policies table

`tbl_privacy_policies` is already part of the installer schema. `ID` is the primary key and `locale` is a unique key:

```sql
CREATE TABLE IF NOT EXISTS {$prefix}tbl_privacy_policies (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'en',
    policy_title VARCHAR(255) NOT NULL,
    policy_content LONGTEXT NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY locale (locale)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Content locale columns

The installer adds indexed locale columns to content tables:

- `tbl_posts.post_locale`
- `tbl_topics.topic_locale`
- `tbl_menu.menu_locale`

### Settings

`install/include/dbtable.php` seeds these settings:

| Setting | Default | Purpose |
|---------|---------|---------|
| `lang_default` | `en` | Default locale |
| `lang_available` | `en,ar,zh,fr,ru,es,id` | Enabled locale codes |
| `lang_auto_detect` | `1` | Enable browser locale detection |
| `lang_prefix_required` | `1` | Enable prefix generation in theme URL helpers |

`lang_prefix_required` is the current key. There is no `lang_prefix_enabled` setting.

---

## 4. Installation & Setup

### `install_i18n_data()`

`install_i18n_data()` lives in `install/include/setup.php` and runs during installation. It:

1. Inserts the seven supported languages.
2. Inserts translations in batches of 100 inside one transaction.
3. Updates `lang_available`, `lang_default`, `lang_auto_detect`, and `lang_prefix_required`.
4. Calls `install_privacy_policy_data()` at the end.

There is no separate `install_frontend_i18n_data()` function. Frontend contexts are part of the same `$translations` array in `install_i18n_data()`.

The current array contains **16 unique contexts and 165 unique keys per language**. With seven locales this produces **1,155 translation rows** (`165 × 7`). The second `form` block was merged into the original `form` context so no keys are silently dropped by PHP duplicate-key handling.

| Context | Keys |
|---------|-----:|
| `navigation` | 63 |
| `form` | 29 |
| `button` | 11 |
| `error` | 5 |
| `footer` | 1 |
| `status` | 5 |
| `visibility` | 6 |
| `admin` | 6 |
| `header` | 5 |
| `sidebar` | 6 |
| `home` | 7 |
| `single` | 4 |
| `cookie_consent` | 6 |
| `404` | 3 |
| `privacy` | 7 |
| `page` | 1 |

`tests/unit/InstallationTest.php` contains a behavioral regression test that captures the installer's prepared statements and asserts the language count, per-locale key count, identical key sets, representative contexts, and transaction counters.

---

## 5. Locale Routing and URL Generation

### Routing behavior

| Mode | Behavior |
|------|----------|
| Permalinks disabled | Theme switcher uses `?switch-lang=...&redirect=...`; `locale_url()` returns the path unchanged |
| Permalinks enabled | `Dispatcher` accepts `/{locale}/...`, sets the locale, strips the prefix, and matches the normal route |
| Direct locale-prefixed URL | Accepted by the dispatcher even when `lang_prefix_required` is off; the setting controls URL generation, not request parsing |
| Default locale in generated theme URLs | Never prefixed by `locale_url()` |

`Bootstrap.php` defines one `locale` route (`/locale`) for the locale controller; it does not define duplicated locale-prefixed routes. `.htaccess` already contains rewrite support for locale-prefixed paths.

### Theme `locale_url()`

`public/themes/blog/functions-i18n.php` implements `locale_url()` as follows:

- Returns the path unchanged when permalinks are disabled.
- Returns the path unchanged when `lang_prefix_required` is off.
- Returns the path unchanged for the default locale.
- Otherwise returns `/{locale}/{path}`.

By contrast, `I18nManager::url()` delegates to `LocaleRouter::buildUrl()`, which always prefixes the supplied locale. The two APIs are not interchangeable.

### Query-string language switching

`lib/main.php` validates `?switch-lang=` as a scalar two-letter code in the supported list, stores it in `$_SESSION['scriptlog_locale']` and the `scriptlog_locale` cookie, then redirects. A `redirect` parameter is accepted only when it is a relative, same-origin path; invalid values redirect to `/`.

---

## 6. Translation Storage, Loading, and Fallback

### Database and generated cache

`TranslationLoader` loads translations through `TranslationDao` and `LanguageDao`, then stores a flat `{key: value}` map in `public/files/cache/translations/{locale}.json`. The default cache lifetime is 3,600 seconds. `invalidate($locale)` removes the file and the in-memory entry; service write paths call invalidation.

### Theme JSON files

Each theme keeps source strings in `public/themes/{theme}/lang/{locale}.json`. The blog theme's global `t()` helper:

1. Resolves the locale with `detect_browser_locale()`.
2. Loads `lang/{locale}.json` once per request via `load_theme_translations()`.
3. Uses `lang/en.json` as a fallback for a missing key.
4. Returns the key itself if neither file contains it.
5. Replaces `%param%` placeholders from the optional `$params` array.

The installer's database seed set and the theme JSON files are maintained separately; the installer currently seeds more keys than the blog theme JSON contains. `ThemeI18nTest` verifies that the keys used by the theme are consistent across language files.

### Core facade

`I18nManager::t()` uses the database/generated cache first, then `loadFromJsonFallback()` against `public/themes/blog/lang/{locale}.json`, then returns the key. Its parameters use `:name` placeholders. This API is available to core/admin consumers; theme templates call the global `t()` helper instead.

---

## 7. Privacy Policy Implementation

### Current implementation

- The table is defined in `install/include/dbtable.php`.
- Default localized policies are inserted by `install_privacy_policy_data()` in `install/include/setup.php`, called from `install_i18n_data()`.
- `lib/dao/PrivacyPolicyDao.php` provides `findByLocale()`, `findDefault()`, `findAllPolicies()`, `createPolicy()`, `updatePolicy()`, `deletePolicy()`, `setDefaultPolicy()`, and `policyExists()`.
- `public/themes/blog/privacy.php` loads the policy for the current locale, falls back to the default policy, and sanitizes permitted HTML with `htmLawed()` before rendering.
- An admin editor and an end-to-end spec cover per-language policy editing.

`locale` is unique but is not the primary key; `ID` is the primary key.

---

## 8. Theme Integration

### Theme i18n helpers

The helpers live in `public/themes/blog/functions-i18n.php`; `functions.php` includes that file along with the other theme helper modules.

| Function | Purpose |
|----------|---------|
| `t()` | Translate a key from theme JSON with `%param%` interpolation |
| `detect_browser_locale()` | Resolve locale from session, cookie, browser header, then default |
| `load_theme_translations()` | Load and memoize a locale's JSON file |
| `locale_url()` | Build a locale-aware URL using permalink and prefix settings |
| `is_locale_prefix_enabled()` | Read `lang_prefix_required` through `ConfigurationDao` |
| `get_default_locale()` | Return the configured/default locale |
| `get_locale()` | Return the active frontend locale |
| `available_locales()` | Return supported locale codes |
| `is_rtl()` / `get_html_dir()` | Return direction information |
| `get_language_name()` | Return native or English language name |
| `language_switcher()` | Render the switcher markup |

`set_locale()` is not a blog-theme helper. LocaleController relies on the session/cookie flow.

### Frontend Language Switcher

Location: `public/themes/blog/header.php`.

The switcher lists `available_locales()` and marks the active locale. When permalinks are disabled, each link uses `?switch-lang={code}&redirect={current URL}`. When permalinks are enabled, links use `locale_url($_SERVER['REQUEST_URI'], $locale)`. The rendered URL is escaped with `theme_escape_html()`.

The dropdown includes ARIA state (`aria-haspopup`, `aria-expanded`, `aria-labelledby`, `aria-current`) and is styled by the theme's RTL assets when applicable.

### Separation from admin

| Aspect | Frontend | Admin |
|--------|----------|-------|
| Session | `$_SESSION['scriptlog_locale']` | `$_SESSION['admin_locale']` |
| Cookie | `scriptlog_locale` | `admin_locale` |
| Query parameter | `?switch-lang=` | `?lang=` |
| Locale helper | `get_locale()` | `admin_get_locale()` |
| Translation helper | `t()` | `admin_translate()` / `admin_t()` |

---

## 9. Translation Keys

The installed database keys and the active theme's JSON files are the sources of truth. The tables below are representative examples, not an exhaustive dictionary.

### Header and sidebar

| Key | English |
|-----|---------|
| `header.nav.home` | Home |
| `header.nav.blog` | Blog |
| `header.nav.search` | Search |
| `sidebar.search.title` | Search |
| `sidebar.latest_posts.title` | Latest Posts |

### Search and pagination

| Key | English |
|-----|---------|
| `search.title` | Search |
| `search.found_results` | Found %count% result(s) for "%keyword%" |
| `search.no_results` | No results found for "%keyword%" |
| `pagination.previous` | Previous |
| `pagination.next` | Next |

### Home and single post

| Key | English |
|-----|---------|
| `home.intro.welcome` | Welcome to ScriptLog |
| `home.intro.description` | Your entryway to a personal blog |
| `single.comment.leave_reply` | Leave a comment |
| `form.name.label` | Name |
| `form.email.label` | Email (will not be published) |

### Footer, consent, errors, and privacy

| Key | English |
|-----|---------|
| `footer.copyright` | All rights reserved |
| `cookie_consent.banner.title` | We value your privacy |
| `404.back_home` | Back to Home |
| `error.wrong_password` | Incorrect password. Please try again. |
| `privacy.page_title` | Privacy Policy |

For the complete current set, inspect `install/include/setup.php` for installer keys and `public/themes/blog/lang/en.json` for theme keys.

---

## 10. Files Reference

### Core files

| File | Current role |
|------|--------------|
| `lib/core/I18nManager.php` | Locale/translation facade for core and admin consumers |
| `lib/core/LocaleDetector.php` | Locale detection and validation |
| `lib/core/LocaleRouter.php` | Prefix extraction/stripping and direct URL building |
| `lib/core/TranslationLoader.php` | Database/cache loading, generated JSON cache, invalidation |
| `lib/core/Dispatcher.php` | Strips valid locale prefixes before route matching |
| `lib/core/Bootstrap.php` | Defines base routes, including `/locale` |
| `lib/controller/LocaleController.php` | Handles the `/locale` route |
| `lib/dao/LanguageDao.php` | Language CRUD |
| `lib/dao/TranslationDao.php` | Translation CRUD and lookups |
| `lib/dao/PrivacyPolicyDao.php` | Privacy policy CRUD |
| `lib/utility/theme-navigation.php` | Locale-aware navigation data |
| `lib/utility/admin-translations.php` | Admin locale helpers and fallback dictionary |
| `lib/main.php` | Frontend `?switch-lang=` handling |

### Installation files

| File | Current role |
|------|--------------|
| `install/include/dbtable.php` | Defines i18n, privacy, and settings tables; seeds locale settings |
| `install/include/setup.php` | `install_i18n_data()` and `install_privacy_policy_data()` |

### Theme files

| File | Current role |
|------|--------------|
| `public/themes/blog/functions-i18n.php` | Frontend i18n helper functions |
| `public/themes/blog/functions.php` | Aggregates theme helper modules |
| `public/themes/blog/header.php` | `lang`/`dir` attributes, RTL assets, language switcher |
| `public/themes/blog/lang/*.json` | Theme translation sources |
| `public/themes/blog/privacy.php` | Loads the localized privacy policy from the database |
| `public/themes/blog/assets/css/rtl.css` | RTL layout styles |
| `public/themes/blog/assets/js/rtl.js` | RTL behavior |

Generated cache files under `public/files/cache/translations/` are runtime artifacts, not source files. Revert incidental cache changes after running tests.

---

## 11. Admin Language Switcher

Location: `admin/navigation.php`; request handling: `admin/index.php`.

The admin switcher uses `?lang=`. `admin/index.php` validates the code, writes `$_SESSION['admin_locale']`, and issues a redirect with the `lang` parameter removed. On an unset locale, `admin_get_locale()` checks the admin session, then the `admin_locale` cookie, then returns `en`. `admin/index.php` also seeds the admin locale from `lang_default` when neither session nor cookie is set.

`admin_translate()` reads `tbl_translations` through `TranslationDao` and falls back to the hardcoded dictionary in `admin-translations.php`; `admin_t()` adds parameter interpolation. The admin sidebar uses `admin_translate('nav.dashboard')`-style keys.

The frontend locale is unaffected by admin switching.

---

## 12. REST API

The i18n REST surface is registered in `api/index.php` under `/api/v1/`:

- `GET /languages`, `/languages/active`, `/languages/default`, `GET|PUT|PATCH|DELETE /languages/{code}`
- `PUT|PATCH /languages/{code}/default`
- `GET /translations/{code}`, `GET /translations/{code}/export`, `GET /translations/{code}/{key}`
- `POST /translations/{code}`, `PUT|PATCH|DELETE /translations/{id}`
- `POST /translations/{code}/import`, `POST /translations/{code}/cache`

Controllers live in `lib/controller/api/LanguagesApiController.php` and `lib/controller/api/TranslationsApiController.php`.

---

## 13. Verification Checklist

The following behavior is covered by the current test suite; see `docs/dev-docs/I18N_TESTING_GUIDE.md` for commands and counts:

- [x] Installer inserts 165 unique keys for each of seven locales exactly once
- [x] Theme i18n keys are consistent across locales
- [x] Locale detection and validation handle session, cookie, and browser values
- [x] Locale prefix extraction and URL generation behave as documented
- [x] Translation loading, generated cache use, and invalidation work
- [x] Frontend language switching validates input and redirects safely
- [x] Admin locale stays separate from frontend locale
- [x] RTL helpers return the correct direction for Arabic
