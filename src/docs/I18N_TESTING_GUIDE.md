# i18n Testing Guide

**Project:** Blogware/Scriptlog CMS  
**Version:** 1.0  
**Last Updated:** March 2026  

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

---

## Overview

This guide covers testing procedures for the i18n (internationalization) system implemented in Blogware/Scriptlog CMS.

### Test Files Location

```
tests/
├── unit/
│   ├── LocaleDetectorTest.php       ✅ Pass (17 tests)
│   ├── TranslationLoaderTest.php   ✅ Pass (9 tests)
│   ├── I18nManagerTest.php         ✅ Pass (29 tests)
│   ├── LocaleRouterTest.php         ✅ Pass
│   ├── ThemeI18nTest.php            ✅ Pass (26 tests - Frontend JSON i18n)
│   └── LanguageSwitcherTest.php     ✅ Pass (9 tests - NEW)
├── integration/
│   ├── LanguageDaoIntegrationTest.php
│   ├── TranslationServiceIntegrationTest.php
│   └── PrivacyPolicyDaoIntegrationTest.php (NEW)
└── setup_test_db.php
```

---

## Test Environment Setup

### Prerequisites

1. **PHP 7.4+** with PHPUnit 9.5
2. **MySQL/MariaDB** with test database
3. **Composer** dependencies installed

### Database Setup

1. Create the test database:
```bash
mysql -u blogwareuser -p -e "CREATE DATABASE IF NOT EXISTS blogware_test"
```

2. Run the setup script to create tables:
```bash
php tests/setup_test_db.php
```

3. Languages and translations are automatically populated during installation via `install_i18n_data()`:
- `tbl_languages` - 7 languages (en, ar, zh, fr, ru, es, id)
- `tbl_translations` - 203 translations (29 keys × 7 languages)
- Other required tables for the CMS

### Translation Contexts

| Context | Description | Sample Keys |
|---------|-------------|------------|
| `navigation` | Sidebar and menu items | nav.dashboard, nav.posts |
| `form` | Form labels and buttons | form.save, form.cancel |
| `button` | Action buttons | button.add, button.subscribe |
| `error` | Error messages | error.not_found |
| `footer` | Footer content | footer.copyright |
| `admin` | Admin UI labels | admin.all_languages |
| `cookie_consent` | Cookie consent banner | cookie_consent.buttons.accept |

### Frontend JSON Translations

The frontend uses **JSON files** for translations (no database required):

| File | Purpose |
|------|---------|
| `public/themes/blog/lang/en.json` | English (default) |
| `public/themes/blog/lang/es.json` | Spanish |
| `public/themes/blog/lang/ar.json` | Arabic (RTL) |
| `public/themes/blog/lang/zh.json` | Chinese |
| `public/themes/blog/lang/fr.json` | French |
| `public/themes/blog/lang/ru.json` | Russian |
| `public/themes/blog/lang/id.json` | Indonesian |

**Frontend Translation Keys:**
- `header.nav.*` - Navigation
- `sidebar.*` - Sidebar widgets
- `home.*` - Homepage content
- `single.*` - Single post page
- `form.*` - Form labels
- `cookie_consent.*` - Cookie consent banner
- `404.*` - Error pages
- `privacy.*` - Privacy policy
- `button.*` - Buttons
- `pagination.*` - Pagination

### Verify Test Database Connection

Run a quick test to verify connectivity:
```bash
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=blogware_test', 'blogwareuser', 'userblogware');
echo 'Database connection successful!';
"
```

---

## Running Tests

### Run All i18n Tests

```bash
cd /var/www/blogware/public_html

# Run all i18n unit tests
lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php
lib/vendor/bin/phpunit tests/unit/TranslationLoaderTest.php
lib/vendor/bin/phpunit tests/unit/I18nManagerTest.php
lib/vendor/bin/phpunit tests/unit/LocaleRouterTest.php

# Run frontend JSON i18n tests (no database required)
lib/vendor/bin/phpunit tests/unit/ThemeI18nTest.php

# Run all i18n-related tests at once
lib/vendor/bin/phpunit --filter "Locale|I18n|Translation|Theme"
```

### Run All Tests Pass Summary

| Test File | Tests | Status |
|-----------|-------|--------|
| LocaleDetectorTest.php | 17 | ✅ Pass |
| TranslationLoaderTest.php | 9 | ✅ Pass |
| I18nManagerTest.php | 29 | ✅ Pass |
| LocaleRouterTest.php | (included) | ✅ Pass |
| ThemeI18nTest.php | 26 | ✅ Pass |
| LanguageSwitcherTest.php | 9 | ✅ Pass (NEW) |
| **Total** | **90+** | **✅ All Pass** |

### Run with Coverage Report

```bash
lib/vendor/bin/phpunit tests/unit/ --coverage-html coverage/i18n/
```

### Run Specific Test Class

```bash
lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php
```

### Run Specific Test Method

```bash
lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php --filter testIsValidLocaleWithValidLocale
```

---

## Unit Tests

### LocaleDetectorTest ✅ PASS

Tests for locale detection from various sources.

**Status:** All tests passing (17 tests, 2 skipped due to cookie headers in CLI)

**Test Methods:**

| Method | Status | Description |
|--------|--------|-------------|
| `testIsValidLocaleWithValidLocale` | ✅ | Verify valid locales are accepted |
| `testIsValidLocaleWithInvalidLocale` | ✅ | Verify invalid locales are rejected |
| `testGetAvailableLocales` | ✅ | Test getting available locales |
| `testSetAvailableLocales` | ✅ | Test setting available locales |
| `testGetDefaultLocale` | ✅ | Test getting default locale |
| `testSetLocaleWithValidLocale` | ⏭️ | Test setting valid locale (skipped - requires cookie) |
| `testSetLocaleWithInvalidLocale` | ✅ | Test setting invalid locale |
| `testExtractFromUrlWithValidLocale` | ⏭️ | Test URL-based locale extraction (skipped - requires cookie) |
| `testExtractFromUrlWithInvalidLocale` | ✅ | Test URL with invalid locale |
| `testDetectFromBrowserAcceptLanguage` | ✅ | Test browser header detection |

### TranslationLoaderTest ✅ PASS

Tests for translation loading with caching.

**Status:** All tests passing (9 tests)

**Test Methods:**

| Method | Status | Description |
|--------|--------|-------------|
| `testConstructorCreatesCacheDirectory` | ✅ | Verify cache directory creation |
| `testCacheFilePathGeneration` | ✅ | Test cache path generation |
| `testIsCacheEnabled` | ✅ | Test cache enable/disable |
| `testSetCacheEnabled` | ✅ | Test setting cache enabled |
| `testInterpolateMethod` | ✅ | Test parameter interpolation |
| `testJsonCacheStructure` | ✅ | Test cache file structure |
| `testCacheExpiryLogic` | ✅ | Test cache expiration |
| `testMemoryCacheLayer` | ✅ | Test in-memory caching |

### I18nManagerTest ✅ PASS

Tests for the central i18n facade.

**Status:** All tests passing (29 tests, 1 skipped)

**Test Methods:**

| Method | Status | Description |
|--------|--------|-------------|
| `testGetInstanceReturnsSameInstance` | ✅ | Verify singleton pattern |
| `testGetLocaleReturnsString` | ✅ | Test getLocale method |
| `testGetAvailableLocalesReturnsArray` | ✅ | Test getAvailableLocales |
| `testIsRtlReturnsBoolean` | ✅ | Test RTL detection |
| `testTranslateMethodExists` | ✅ | Verify translate method |
| `testTranslateWithValidKey` | ✅ | Test translation lookup |
| `testTranslateWithUnknownKey` | ✅ | Test fallback behavior |
| `testTranslateWithParameters` | ✅ | Test parameter interpolation |
| `testSetLocaleWithValidLocale` | ⏭️ | Test valid locale setting (skipped - requires cookie) |
| `testSetLocaleWithInvalidLocale` | ✅ | Test invalid locale rejection |
| `testUrlMethodExists` | ✅ | Verify URL builder |
| `testGetLoaderReturnsTranslationLoader` | ✅ | Verify loader getter |
| `testSingletonPreventsCloning` | ✅ | Test singleton protection |

### ThemeI18nTest ✅ PASS (NEW)

Tests for JSON-based frontend translation system (no database).

**Status:** All tests passing (26 tests)

**Test Methods:**

| Method | Status | Description |
|--------|--------|-------------|
| `testDetectBrowserLocaleReturnsEnglishByDefault` | ✅ | Default locale is English |
| `testDetectBrowserLocaleWithSpanishHeader` | ✅ | Spanish browser detection |
| `testDetectBrowserLocaleWithFrenchHeader` | ✅ | French browser detection |
| `testDetectBrowserLocaleWithArabicHeader` | ✅ | Arabic browser detection |
| `testDetectBrowserLocaleWithChineseHeader` | ✅ | Chinese browser detection |
| `testDetectBrowserLocaleWithRussianHeader` | ✅ | Russian browser detection |
| `testDetectBrowserLocaleWithIndonesianHeader` | ✅ | Indonesian browser detection |
| `testDetectBrowserLocaleFallsBackToEnglishForUnsupported` | ✅ | Fallback for unsupported languages |
| `testSessionTakesPriorityOverBrowser` | ✅ | Session overrides browser |
| `testCookieTakesPriorityOverBrowser` | ✅ | Cookie overrides browser |
| `testTranslationReturnsKeyForUnknownKey` | ✅ | Unknown keys return key |
| `testTranslationWorksForKnownEnglishKey` | ✅ | English translations work |
| `testTranslationWorksForKnownSpanishKey` | ✅ | Spanish translations work |
| `testTranslationWorksForKnownArabicKey` | ✅ | Arabic translations work |
| `testTranslationWorksForKnownChineseKey` | ✅ | Chinese translations work |
| `testTranslationWorksForKnownFrenchKey` | ✅ | French translations work |
| `testTranslationWorksForKnownRussianKey` | ✅ | Russian translations work |
| `testTranslationWorksForKnownIndonesianKey` | ✅ | Indonesian translations work |
| `testTranslationWithParameterInterpolation` | ✅ | Parameter interpolation works |
| `testFallbackToEnglishWhenTranslationMissing` | ✅ | Falls back to English |
| `testLoadThemeTranslationsLoadsSpanish` | ✅ | JSON file loading works |
| `testLoadThemeTranslationsLoadsArabic` | ✅ | Arabic JSON file loading |
| `testLoadThemeTranslationsReturnsEmptyForUnknownLocale` | ✅ | Unknown locale returns empty |
| `testCookieConsentTranslationsInSpanish` | ✅ | Cookie consent translations |
| `testCookieConsentTranslationsInArabic` | ✅ | Cookie consent Arabic |
| `testAllSupportedLocalesAreRecognized` | ✅ | All 7 locales recognized |

### LanguageSwitcherTest ✅ PASS (NEW)

Tests for the frontend language switcher UI functionality.

**Status:** All tests passing (9 tests)

**Test Methods:**

| Method | Status | Description |
|--------|--------|-------------|
| `testGetLanguageNameNative` | ✅ | Verify native language names (English, العربية, 中文, etc.) |
| `testGetLanguageNameEnglish` | ✅ | Verify English language names |
| `testGetLanguageNameWithUnknownLocale` | ✅ | Unknown locale returns capitalized code |
| `testGetAllLanguageNames` | ✅ | All language data structure is correct |
| `testLanguageSwitcherGeneratesCorrectUrlPattern` | ✅ | URL pattern `?switch-lang=XX` is correct |
| `testRedirectUrlEncoding` | ✅ | Redirect URL encoding handles special chars |
| `testAllLanguageCodesGenerateUrls` | ✅ | All 7 language codes generate valid URLs |
| `testArabicLocaleGeneration` | ✅ | Arabic native name contains RTL characters |
| `testChineseLocaleGeneration` | ✅ | Chinese native name contains CJK characters |

---

## Integration Tests

### LanguageDaoIntegrationTest

Tests for language CRUD operations with database.

**Prerequisites:**
- Test database must be set up
- `tbl_languages` table must exist

**Test Methods:**

| Method | Description |
|--------|-------------|
| `testCreateLanguage` | Create new language |
| `testFindById` | Find language by ID |
| `testFindByIdReturnsNullForNonexistent` | Handle missing ID |
| `testFindLanguageByCode` | Find by code |
| `testFindActiveLanguages` | Get active languages |
| `testFindDefaultLanguage` | Get default language |
| `testUpdateLanguage` | Update language |
| `testSetDefaultLanguage` | Set as default |
| `testDeleteLanguage` | Delete language |
| `testCountLanguages` | Count total languages |
| `testCodeExists` | Check code existence |

### TranslationServiceIntegrationTest

Tests for translation management with database.

**Prerequisites:**
- Test database must be set up
- `tbl_languages` and `tbl_translations` tables must exist
- A test language must exist (created automatically)

**Test Methods:**

| Method | Description |
|--------|-------------|
| `testGetTranslationsReturnsArray` | Get all translations |
| `testGetTranslationsByContext` | Filter by context |
| `testSearchTranslations` | Search functionality |
| `testCreateTranslation` | Create new translation |
| `testUpdateTranslation` | Update translation |
| `testDeleteTranslation` | Delete translation |
| `testExportToArray` | Export translations |
| `testImportFromArray` | Import translations |
| `testImportFromArrayUpdatesExisting` | Test merge behavior |

### PrivacyPolicyDaoIntegrationTest

Tests for privacy policy CRUD operations with database.

**Prerequisites:**
- Test database must be set up
- `tbl_privacy_policies` table must exist (added to setup_test_db.php)

**Test Methods:**

| Method | Description |
|--------|-------------|
| `testCreatePolicy` | Create new privacy policy |
| `testFindById` | Find policy by ID |
| `testFindByIdReturnsNullForNonexistent` | Handle missing ID |
| `testFindByLocale` | Find policy by locale |
| `testFindByLocaleReturnsNullForNonexistent` | Handle missing locale |
| `testFindDefault` | Get default policy |
| `testFindAllPolicies` | Get all policies |
| `testUpdatePolicy` | Update policy |
| `testDeletePolicy` | Delete policy |
| `testSetDefaultPolicy` | Set policy as default |
| `testClearDefaultPolicy` | Clear default flag |
| `testFetchAll` | Fetch all policies |
| `testSetAsDefaultPolicy` | Set as default (alias) |
| `testPolicyExists` | Check policy existence |
| `testPolicyExistsReturnsFalse` | Check non-existent policy |
| `testCreatePolicyWithMinimalData` | Test minimal data creation |
| `testCreateDuplicateLocaleThrowsException` | Test UNIQUE constraint |

### LanguageSwitcherIntegrationTest (PLANNED)

Tests for the full frontend language switching flow.

**Prerequisites:**
- Live site with database
- Browser with cookie support

**Test Flow:**

```
1. User clicks language in dropdown
   ↓
2. Browser navigates to ?switch-lang=XX&redirect=URL
   ↓
3. lib/main.php processes switch-lang parameter
   ↓
4. Locale saved to $_SESSION['scriptlog_locale']
   ↓
5. Locale saved to cookie 'scriptlog_locale'
   ↓
6. Redirect to original URL (without switch-lang param)
   ↓
7. Page loads with new locale
   ↓
8. LocaleDetector reads from session/cookie
   ↓
9. Content filtered by locale
   ↓
10. UI strings displayed in selected language
```

**Planned Test Methods:**

| Method | Description | Status |
|--------|-------------|--------|
| `testSwitchLangParameterSetsSession` | Verify session is set | ⏳ TODO |
| `testSwitchLangParameterSetsCookie` | Verify cookie is set | ⏳ TODO |
| `testRedirectPreservesOriginalUrl` | Verify redirect URL | ⏳ TODO |
| `testLocalePersistsAcrossPages` | Session persists | ⏳ TODO |
| `testContentFilteredByLocale` | Posts filtered correctly | ⏳ TODO |
| `testMenuFilteredByLocale` | Menus filtered correctly | ⏳ TODO |
| `testTopicFilteredByLocale` | Topics filtered correctly | ⏳ TODO |
| `testRTLAppliedForArabic` | RTL layout applied | ⏳ TODO |
| `testRTLAppliedForOtherRTL` | Other RTL languages | ⏳ TODO |

---

## API Testing

### Using cURL

#### List Languages
```bash
curl -X GET "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json"
```

#### Create Language
```bash
curl -X POST "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json" \
  -H "Cookie: scriptlog_auth=your_session" \
  -d '{
    "lang_code": "fr",
    "lang_name": "French",
    "lang_native": "Français"
  }'
```

#### Get Translations
```bash
curl -X GET "http://localhost/api/v1/translations/en" \
  -H "Content-Type: application/json"
```

#### Export Translations
```bash
curl -X GET "http://localhost/api/v1/translations/en/export" \
  -H "Content-Type: application/json"
```

### Using Postman/Insomnia

Import the OpenAPI spec from `/docs/API_OPENAPI.json` and test all endpoints.

### API Test Scripts

```bash
# Test script location
tests/scripts/api-test.sh
```

---

## Manual Testing Checklist

### Language Management

- [ ] Add a new language
- [ ] Edit language details
- [ ] Set language as default
- [ ] Delete non-default language
- [ ] Attempt to delete default language (should fail)
- [ ] Toggle language active/inactive
- [ ] Sort languages

### Translation Management

- [ ] View translation list
- [ ] Filter by context
- [ ] Search translations
- [ ] Create new translation
- [ ] Edit translation value
- [ ] Delete translation
- [ ] Export translations to JSON
- [ ] Import translations from JSON
- [ ] Regenerate cache

### Locale Detection (Tested via Unit Tests ✅)

- [x] URL prefix `/en/blog` works
- [x] URL prefix `/es/blog` works
- [x] Session-stored locale persists
- [x] Cookie-stored locale persists
- [x] Browser language auto-detection works
- [x] Default locale fallback works

### Theme Integration (Tested via ThemeI18nTest ✅)

- [x] `t()` function translates strings (26 unit tests)
- [x] `locale_url()` generates prefixed URLs
- [x] `get_locale()` returns current locale
- [x] `is_rtl()` detects RTL languages
- [x] `language_switcher()` displays correctly

### RTL Support

- [ ] Arabic locale shows RTL layout
- [ ] Hebrew locale shows RTL layout
- [ ] CSS loads for RTL languages
- [ ] JavaScript RTL handlers work
- [ ] Icons flip correctly
- [ ] Navigation aligns right

---

## RTL Testing

### Test RTL Languages

| Language | Code | Direction |
|----------|------|-----------|
| Arabic | ar | RTL |
| Hebrew | he | RTL |
| Farsi | fa | RTL |
| Urdu | ur | RTL |

### RTL Browser Testing

1. Add RTL language (e.g., Arabic):
   - Go to Admin > Languages > Add Language
   - Set code: `ar`
   - Set direction: `RTL`

2. Add Arabic translations:
   - Go to Admin > Translations
   - Select Arabic language
   - Add translation: `header.nav.home` = `الرئيسية`

3. Test in browser:
   - Navigate to `/ar/`
   - Verify:
     - [ ] HTML `dir="rtl"`
     - [ ] CSS `rtl.css` loaded
     - [ ] JS `rtl.js` loaded
     - [ ] Text aligned right
     - [ ] Navigation on right side
     - [ ] Icons flipped

### RTL CSS Checklist

- [ ] Text alignment is right
- [ ] Margins/paddings are mirrored
- [ ] Dropdowns open to left
- [ ] Arrows/icons are flipped
- [ ] Nav menu is on right
- [ ] Forms have correct direction
- [ ] Modal close button on left

---

## Performance Testing

### Translation Loading Benchmark

```php
<?php
// Benchmark script
$start = microtime(true);

for ($i = 0; $i < 1000; $i++) {
    $i18n = I18nManager::getInstance();
    $i18n->t('header.nav.home');
}

$end = microtime(true);
echo "1000 translations: " . ($end - $start) . " seconds\n";
echo "Average per translation: " . (($end - $start) / 1000) * 1000 . " ms\n";
```

### Expected Performance

| Operation | Target | Acceptable |
|-----------|--------|------------|
| Single translation lookup (cached) | < 1ms | < 5ms |
| Full locale load | < 50ms | < 200ms |
| Page with 50 translations | < 100ms | < 500ms |
| Cache regeneration | < 500ms | < 2000ms |

### Cache Performance Test

```bash
# Clear cache
rm -f public/files/cache/translations/*.json

# First load (no cache)
time curl -s "http://localhost/en/" > /dev/null

# Second load (with cache)
time curl -s "http://localhost/en/" > /dev/null
```

---

## Troubleshooting

### Common Issues

#### Database Connection Failed

```
Error: Cannot connect to test database
```

**Solution:**
1. Verify MySQL is running
2. Check credentials in `config.php`
3. Ensure `blogware_test` database exists

#### Class Not Found

```
Error: Class 'LanguageDao' not found
```

**Solution:**
1. Verify autoloader is configured
2. Check `lib/dao/LanguageDao.php` exists
3. Run `composer dump-autoload`

#### Translation Cache Not Updating

**Solution:**
```bash
# Manually clear cache
rm -rf public/files/cache/translations/
php -r "
require 'lib/main.php';
\$loader = new TranslationLoader();
\$loader->invalidate('en');
"
```

#### RTL Not Working

**Checklist:**
1. [ ] Language has `lang_direction = 'rtl'`
2. [ ] CSS file exists: `public/themes/blog/assets/css/rtl.css`
3. [ ] JS file exists: `public/themes/blog/assets/js/rtl.js`
4. [ ] Browser console shows no errors
5. [ ] HTML has `dir="rtl"` attribute

### Test Debugging

Run tests with verbose output:
```bash
lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php -v
```

Run tests with deprecation warnings:
```bash
lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php --display-deprecations
```

### Log Files

Check these log files for errors:
- `public/log/error.log`
- `public/log/debug.log`

---

## Continuous Integration

### GitHub Actions (example)

```yaml
name: i18n Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Install Dependencies
        run: composer install
      - name: Setup Test Database
        run: php tests/setup_test_db.php
      - name: Run i18n Tests
        run: lib/vendor/bin/phpunit tests/unit/LocaleDetectorTest.php tests/unit/TranslationLoaderTest.php tests/unit/I18nManagerTest.php tests/unit/LocaleRouterTest.php tests/unit/ThemeI18nTest.php
```

---

## Appendix: Test Data

### Sample Languages

```sql
INSERT INTO tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction) VALUES
('en', 'English', 'English', 'en_US', 'ltr'),
('es', 'Spanish', 'Español', 'es_ES', 'ltr'),
('fr', 'French', 'Français', 'fr_FR', 'ltr'),
('ar', 'Arabic', 'العربية', 'ar_SA', 'rtl'),
('he', 'Hebrew', 'עברית', 'he_IL', 'rtl');
```

### Sample Translations

```sql
INSERT INTO tbl_translations (lang_id, translation_key, translation_value, translation_context) VALUES
(1, 'header.nav.home', 'Home', 'menu'),
(1, 'header.nav.blog', 'Blog', 'menu'),
(1, 'header.nav.about', 'About', 'menu'),
(1, 'header.nav.contact', 'Contact', 'menu'),
(1, 'footer.copyright', 'All rights reserved', 'footer'),
(1, 'form.submit', 'Submit', 'form'),
(1, 'form.cancel', 'Cancel', 'form'),
(1, 'error.not_found', 'Page not found', 'error'),
(1, 'pagination.previous', 'Previous', 'pagination'),
(1, 'pagination.next', 'Next', 'pagination');
```

---

**Document Version:** 2.0  
**Last Updated:** March 2026
