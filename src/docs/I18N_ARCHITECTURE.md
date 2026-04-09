# i18n (Internationalization & Localization) Architecture Plan

**Project:** Blogware/Scriptlog CMS  
**Version:** 2.0  
**Last Updated:** April 2026  
**Status:** ✅ Completed

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Database Schema](#database-schema)
4. [Installation & Setup](#installation--setup)
5. [Locale Prefix Routing](#locale-prefix-routing)
6. [Fallback Mechanism](#fallback-mechanism)
7. [Privacy Policy Implementation](#privacy-policy-implementation)
8. [Theme Integration](#theme-integration)
9. [Translation Keys Dictionary](#translation-keys-dictionary)
10. [Files Reference](#files-reference)

---

## 1. Executive Summary

This document outlines the complete architecture for implementing internationalization (i18n) and localization features in Blogware/Scriptlog CMS.

### Key Requirements

| Requirement | Implementation |
|-------------|----------------|
| **Scope** | Full i18n - UI strings, menus, themes, user-generated content |
| **Content Strategy** | Separate content per locale (posts, pages, topics, menus have locale) |
| **URL Structure** | Prefix-based when permalinks enabled: `/en/`, `/es/`, `/fr/` |
| **Translation Storage** | Hybrid: Database primary + JSON file cache |
| **Default Language** | No prefix in URLs for default language (e.g., `/post/1/slug` stays as-is) |
| **Locale Prefix** | Always enabled when permalinks enabled, configurable via admin settings |

### Architecture Highlights

- **Extendable**: Simple admin settings toggle for locale prefix
- **Scalable**: JSON caching for high-performance
- **Agile**: Modular design with clear separation of concerns
- **Secure**: CSRF protection, XSS prevention, prepared statements
- **Fast**: Multi-layer caching with lazy loading

---

## 2. Architecture Overview

### Request Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     REQUEST FLOW (i18n)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   Permalinks ENABLED:  /en/post/1/slug                           │
│   Permalinks DISABLED: ?p=1  (default language, no prefix)      │
│       │                                                         │
│       ▼                                                         │
│   ┌──────────────────────┐                                      │
│   │   LocaleDetector     │  → Detect/validate locale from URL   │
│   └──────────┬───────────┘                                      │
│              │                                                  │
│              ▼                                                  │
│   ┌──────────────────────┐                                      │
│   │   TranslationLoader  │  → Load UI strings (DB → Cache)      │
│   └──────────┬───────────┘                                      │
│              │                                                  │
│              ▼                                                  │
│   ┌──────────────────────┐                                      │
│   │   ContentFilter      │  → Filter content by locale          │
│   └──────────┬───────────┘                                      │
│              │                                                  │
│              ▼                                                  │
│   ┌──────────────────────┐                                      │
│   │   Theme Render       │  → Render with translated strings    │
│   └──────────┘                                                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Fallback Priority

When a translation key is requested, the system checks in this order:

1. **Database** (`tbl_translations`) - Primary storage
2. **JSON Files** (`public/themes/blog/lang/{locale}.json`) - Fallback
3. **Key as-is** - Return the key if no translation found

---

## 3. Database Schema

### Existing Tables

#### tbl_languages

Stores available languages.

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

#### tbl_translations

Stores UI/interface translation strings.

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
    KEY translation_key (translation_key(191)),
    FOREIGN KEY (lang_id) REFERENCES {$prefix}tbl_languages(ID) ON DELETE CASCADE
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### New Table: tbl_privacy_policies

Privacy policy content stored per locale.

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

### Existing Content Locale Columns

```sql
-- tbl_posts already has post_locale
ALTER TABLE {$prefix}tbl_posts 
    ADD COLUMN post_locale VARCHAR(10) NOT NULL DEFAULT 'en' AFTER post_type;

-- tbl_topics already has topic_locale
ALTER TABLE {$prefix}tbl_topics 
    ADD COLUMN topic_locale VARCHAR(10) NOT NULL DEFAULT 'en' AFTER topic_status;

-- tbl_menu already has menu_locale
ALTER TABLE {$prefix}tbl_menu 
    ADD COLUMN menu_locale VARCHAR(10) NOT NULL DEFAULT 'en' AFTER menu_sort;
```

### Settings

```sql
-- Default language code
INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) 
VALUES ('lang_default', 'en');

-- Available languages (comma-separated codes)
INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) 
VALUES ('lang_available', 'en,ar,zh,fr,ru,es,id');

-- Auto-detect browser language (0 or 1)
INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) 
VALUES ('lang_auto_detect', '1');

-- Locale prefix in URLs (only when permalinks enabled)
INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) 
VALUES ('lang_prefix_enabled', '1');
```

---

## 4. Installation & Setup

### Installation Function: install_i18n_data()

Located in `install/include/setup.php`, this function is called during installation.

**Supported Languages (7):**
- English (en) - default
- Arabic (ar) - RTL
- Chinese (zh)
- French (fr)
- Russian (ru)
- Spanish (es)
- Indonesian (id)

**Translation Contexts:**

| Context | Description | Keys |
|---------|-------------|------|
| `navigation` | Admin navigation | ~35 keys |
| `form` | Form labels | ~8 keys |
| `button` | Buttons | ~3 keys |
| `error` | Error messages | ~3 keys |
| `footer` | Footer text | ~1 key |
| `admin` | Admin UI | ~5 keys |

### Frontend Translations

Frontend translations are dynamically loaded into `tbl_translations` during installation using the `install_frontend_i18n_data()` function.

---

## 5. Locale Prefix Routing

### Behavior Summary

| Permalinks | Locale Prefix | Default Language URL |
|------------|---------------|---------------------|
| Disabled | Never | `/` (no change) |
| Enabled | Configurable | `/post/1/slug` (no prefix for 'en') |
| Enabled + Lang changed | Yes | `/es/post/1/slug` |

### Configuration

- **Setting**: `lang_prefix_enabled` in `tbl_settings`
- **Admin Toggle**: Simple checkbox in Language Settings
- **Enable Condition**: Only active when permalinks are enabled (`rewrite` = 'yes')

### Route Pattern Changes (Bootstrap.php)

When locale prefix enabled:
```php
// Default language (no prefix)
'single' => "/post/(?'id'\d+)/(?'post'[\w\-]+)"

// With locale prefix (non-default languages)
'single_with_locale' => "/(?>'locale'[\w\-]+)/post/(?'id'\d+)/(?'post'[\w\-]+)"
```

### URL Building Logic

```php
function locale_url(string $path, ?string $locale = null): string
{
    $i18n = I18nManager::getInstance();
    $defaultLocale = $i18n->getDetector()->getDefaultLocale();
    $currentLocale = $locale ?? $i18n->getLocale();
    $permalinksEnabled = rewrite_status() === 'yes';
    $prefixEnabled = $permalinksEnabled && is_locale_prefix_enabled();
    
    // Default language: no prefix
    if ($currentLocale === $defaultLocale && !$prefixEnabled) {
        return $path;
    }
    
    // Non-default or prefix enabled:
    return "/{$currentLocale}{$path}";
}
```

---

## 6. Fallback Mechanism

### I18nManager Enhancement

```php
public function t(string $key, array $params = [], ?string $locale = null): string
{
    if (!$this->initialized) {
        $this->initialize();
    }

    $locale = $locale ?? $this->locale;

    // Priority 1: DB translations (already loaded)
    $value = $this->translations[$key] ?? null;

    // Priority 2: Fallback to JSON file
    if ($value === null) {
        $value = $this->loadFromJsonFallback($key, $locale);
    }

    // Priority 3: Return key as-is (no translation found)
    if ($value === null) {
        $value = $key;
    }

    // Handle parameters
    if (!empty($params)) {
        return $this->interpolate($value, $params);
    }

    return $value;
}

private function loadFromJsonFallback(string $key, string $locale): ?string
{
    $jsonPath = APP_ROOT . 'public' . DS . 'themes' . DS . 'blog' . DS . 'lang' . DS . $locale . '.json';
    
    if (!file_exists($jsonPath)) {
        return null;
    }

    $jsonContent = file_get_contents($jsonPath);
    $translations = json_decode($jsonContent, true);
    
    return $translations[$key] ?? null;
}
```

---

## 7. Privacy Policy Implementation

### Best Practices

- Simple table structure with locale as primary key
- Default policy used if locale not found
- Admin interface to edit policies per language
- HTML content allowed for formatting

### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| ID | BIGINT | Primary key |
| locale | VARCHAR(10) | Language code (en, ar, zh, etc.) |
| policy_title | VARCHAR(255) | Title in that language |
| policy_content | LONGTEXT | Full HTML content |
| is_default | TINYINT | 1 if this is fallback |
| created_at | TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | Last update |

### PrivacyDao Methods

```php
class PrivacyPolicyDao extends Dao
{
    public function findByLocale(string $locale): ?array;
    public function findDefault(): ?array;
    public function createPolicy(array $data): int;
    public function updatePolicy(int $id, array $data): void;
    public function deletePolicy(int $id): void;
}
```

---

## 8. Theme Integration

### Theme Files Modified

| File | Purpose |
|------|---------|
| `functions.php` | i18n helper functions (t(), locale_url(), get_locale(), is_rtl(), available_locales(), language_switcher()) |
| `header.php` | Dynamic lang/dir attributes, RTL CSS loading, language switcher |
| `footer.php` | Copyright translation, RTL JS loading |
| `sidebar.php` | Widget titles |
| `single.php` | Comment form |
| `cookie-consent.php` | Consent banner |
| `privacy.php` | Load from database |
| `404.php` | Error page |
| `home.php` | Hero section, latest posts |
| `category.php`, `tag.php`, `archive.php`, `page.php`, `blog.php` | Section headers |

### Translation Helper Usage

```php
// Basic translation
<?= t('sidebar.search.title'); ?>

// With locale URL
<a href="<?= locale_url('/post/1/slug'); ?>">Link</a>

// Current locale
<?= get_locale(); ?>  // Returns: 'en', 'ar', etc.

// RTL check
<?php if (is_rtl()): ?>
    <link rel="stylesheet" href="rtl.css">
<?php endif; ?>

// Language direction
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">
```

---

## 9. Translation Keys Dictionary

### Navigation (header)

| Key | English |
|-----|---------|
| header.nav.home | Home |
| header.nav.blog | Blog |
| header.nav.about | About |
| header.nav.contact | Contact |
| header.nav.search | Search |

### Sidebar

| Key | English |
|-----|---------|
| sidebar.search.title | Search |
| sidebar.search.placeholder | What are you looking for? |
| sidebar.latest_posts.title | Latest Posts |
| sidebar.categories.title | Categories |
| sidebar.archives.title | Archives |
| sidebar.tags.title | Tags |

### Home Page

| Key | English |
|-----|---------|
| home.hero.discover_more | Discover More |
| home.hero.admin_panel | Go to administrator panel |
| home.hero.scroll_down | Scroll Down |
| home.intro.welcome | Welcome to ScriptLog |
| home.intro.description | Your entryway to a personal blog... |
| home.latest_posts.title | Latest from the blog |
| home.divider.view_more | View More |

### Single Post

| Key | English |
|-----|---------|
| single.comment.leave_reply | Leave a comment |
| single.comment.label | Type your comment |
| single.comment.placeholder | Enter your comment |
| form.name.label | Name |
| form.name.placeholder | Enter name |
| form.email.label | Email (will not be published) |
| form.email.placeholder | Enter email |
| single.comment.submit | Submit Comment |

### Footer

| Key | English |
|-----|---------|
| footer.copyright | All rights reserved |

### Cookie Consent

| Key | English |
|-----|---------|
| cookie_consent.banner.title | We value your privacy |
| cookie_consent.banner.description | ...uses cookies to enhance... |
| cookie_consent.buttons.accept | Accept All |
| cookie_consent.buttons.reject | Reject All |
| cookie_consent.buttons.learn_more | Learn More |
| cookie_consent.privacy.link | Privacy Policy |

### 404 Page

| Key | English |
|-----|---------|
| 404.title | 404 |
| 404.message | The page you are looking for was not found. |
| 404.back_home | Back to Home |

---

## 10. Files Reference

### Core Files (lib/)

| File | Purpose | Status |
|------|---------|--------|
| lib/core/I18nManager.php | Central i18n facade | ✅ Exists |
| lib/core/LocaleDetector.php | Locale detection | ✅ Exists |
| lib/core/LocaleRouter.php | Locale-prefixed routing | ✅ Exists |
| lib/core/TranslationLoader.php | Load translations | ✅ Exists |
| lib/dao/LanguageDao.php | Language CRUD | ✅ Exists |
| lib/dao/TranslationDao.php | Translation CRUD | ✅ Exists |
| lib/core/PrivacyPolicyDao.php | Privacy policy CRUD | 🆕 To create |

### Installation Files (install/)

| File | Purpose | Status |
|------|---------|--------|
| install/include/dbtable.php | Table definitions | 🆕 Add privacy table |
| install/include/setup.php | Installation functions | 🆕 Add frontend i18n data |

### Theme Files (public/themes/blog/)

| File | Changes Required |
|------|------------------|
| functions.php | Already has helper functions ✅ |
| header.php | Already has lang/dir/rtl support ✅ |
| footer.php | 1 string replacement |
| sidebar.php | 6 string replacements |
| single.php | 8 string replacements |
| cookie-consent.php | 7 string replacements |
| 404.php | 2 string replacements |
| home.php | 3 string replacements |
| privacy.php | Load from database |
| category.php, tag.php, archive.php, page.php, blog.php | Section titles |

### Configuration Files

| File | Changes |
|------|---------|
| lib/core/Bootstrap.php | Add locale prefix to routes |
| lib/core/Dispatcher.php | Extract locale before routing |
| lib/core/RequestPath.php | Handle locale in path |
| .htaccess | Rewrite rules for locale prefixes |

---

## Implementation Phases

### Phase 1: Database & Installation
- Add `tbl_privacy_policies` to dbtable.php
- Add privacy policy data to install_i18n_data()
- Add frontend translations to installation

### Phase 2: Fallback Enhancement
- Enhance I18nManager for JSON fallback

### Phase 3: Privacy Policy Database
- Create PrivacyPolicyDao
- Create admin interface for policy management
- Update privacy.php template

### Phase 4: Locale Prefix Routing
- Update Bootstrap.php with locale routes
- Update Dispatcher.php for locale extraction
- Add locale prefix setting check

### Phase 5: Theme Template Updates
- Update all templates with t() calls

---

## Testing Checklist

- [ ] Default language ('en') URLs: `/post/1/slug` works without prefix
- [ ] Non-default language URLs: `/es/post/1/slug` shows Spanish
- [ ] RTL layout works for Arabic locale
- [ ] Language switcher generates correct URLs
- [ ] Privacy policy loads from database per locale
- [ ] Translation fallback works (DB → JSON → Key)
- [ ] Permalinks disabled: No locale prefix in any URL

---

**Document Version:** 2.0  
**Last Updated:** March 2026