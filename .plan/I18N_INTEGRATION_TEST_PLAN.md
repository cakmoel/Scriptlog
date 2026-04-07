# Language Switcher Integration Tests - Implementation Plan

**Project:** Blogware/Scriptlog CMS  
**Date:** April 2026  
**Status:** Planning  

---

## Executive Summary

This document outlines the implementation plan for integration tests that verify the complete frontend language switching flow. These tests will run against a live or simulated environment to validate the end-to-end functionality.

---

## Current State

### Unit Tests Passing ✅
- `LanguageSwitcherTest.php` - 9 tests, 42 assertions
- `LocaleDetectorTest.php` - 17 tests
- `ThemeI18nTest.php` - 26 tests

### What's NOT Tested Yet
The full frontend language switching flow from click to display is not automated:
1. User clicks language dropdown → URL changes to `?switch-lang=XX`
2. `lib/main.php` processes `switch-lang` parameter
3. Session/cookie is set
4. User is redirected to original URL
5. Page displays in new language

---

## Integration Test Implementation Plan

### Phase 1: HTTP Integration Tests

Create `tests/integration/LanguageSwitcherIntegrationTest.php` that simulates HTTP requests.

#### Test 1.1: Switch-Lang Parameter Processing
```php
public function testSwitchLangParameterIsProcessed()
{
    // Simulate request: /?switch-lang=ar
    $_GET['switch-lang'] = 'ar';
    $_SERVER['REQUEST_URI'] = '/';
    
    // Process through main.php logic
    $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($_GET['switch-lang']));
    
    $this->assertEquals('ar', $langCode);
    $this->assertTrue(in_array($langCode, ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id']));
}
```

#### Test 1.2: Redirect URL Construction
```php
public function testRedirectUrlIsProperlyConstructed()
{
    $_SERVER['REQUEST_URI'] = '/blog/my-post?foo=bar';
    
    // Simulate redirect logic from lib/main.php
    $urlParts = parse_url($_SERVER['REQUEST_URI']);
    $path = $urlParts['path'] ?? '/';
    parse_str($urlParts['query'] ?? '', $query);
    unset($query['switch-lang']);
    $newQuery = !empty($query) ? '?' . http_build_query($query) : '';
    $redirectUrl = $path . $newQuery;
    
    $this->assertEquals('/blog/my-post?foo=bar', $redirectUrl);
}
```

#### Test 1.3: Session/Cookie Storage
```php
public function testLocaleIsStoredInSessionAndCookie()
{
    // Simulate the logic from lib/main.php
    $langCode = 'ar';
    
    $_SESSION['scriptlog_locale'] = $langCode;
    
    // Verify session is set
    $this->assertEquals('ar', $_SESSION['scriptlog_locale']);
}
```

### Phase 2: Database Integration Tests

Create tests that verify content filtering by locale.

#### Test 2.1: Posts Filtered by Locale
```php
public function testPostsAreFilteredByLocale()
{
    // Create test posts with different locales
    // Post 1: post_locale = 'en'
    // Post 2: post_locale = 'ar'
    
    // Set current locale to 'ar'
    $_SESSION['scriptlog_locale'] = 'ar';
    
    // Fetch posts - should only return 'ar' posts
    // Verify Post 2 is returned, Post 1 is filtered out
}
```

#### Test 2.2: Topics Filtered by Locale
```php
public function testTopicsAreFilteredByLocale()
{
    // Create test topics with different locales
    // Set locale, verify filtering works
}
```

#### Test 2.3: Menu Items Filtered by Locale
```php
public function testMenuItemsAreFilteredByLocale()
{
    // Create test menu items with different locales
    // Set locale, verify only matching items shown
}
```

### Phase 3: RTL Integration Tests

Verify RTL layout is applied correctly.

#### Test 3.1: RTL Detection
```php
public function testRTLIsDetectedForArabic()
{
    $_SESSION['scriptlog_locale'] = 'ar';
    
    // Simulate is_rtl() check
    $rtlLocales = ['ar'];
    $isRtl = in_array($_SESSION['scriptlog_locale'], $rtlLocales);
    
    $this->assertTrue($isRtl);
}
```

#### Test 3.2: HTML Direction Attribute
```php
public function testHtmlDirAttributeIsSetCorrectly()
{
    // Test LTR
    $_SESSION['scriptlog_locale'] = 'en';
    $dir = ($_SESSION['scriptlog_locale'] === 'ar') ? 'rtl' : 'ltr';
    $this->assertEquals('ltr', $dir);
    
    // Test RTL
    $_SESSION['scriptlog_locale'] = 'ar';
    $dir = ($_SESSION['scriptlog_locale'] === 'ar') ? 'rtl' : 'ltr';
    $this->assertEquals('rtl', $dir);
}
```

### Phase 4: End-to-End Flow Tests

Simulate the complete user journey.

#### Test 4.1: Complete Language Switch Flow
```php
public function testCompleteLanguageSwitchFlow()
{
    // Step 1: User is on English page
    $_SESSION['scriptlog_locale'] = 'en';
    $this->assertEquals('en', $_SESSION['scriptlog_locale']);
    
    // Step 2: User clicks Arabic
    $_GET['switch-lang'] = 'ar';
    $_SERVER['REQUEST_URI'] = '/blog/my-post';
    
    // Step 3: Process switch-lang
    $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($_GET['switch-lang']));
    $_SESSION['scriptlog_locale'] = $langCode;
    
    // Step 4: Verify locale changed
    $this->assertEquals('ar', $_SESSION['scriptlog_locale']);
    $this->assertTrue(is_rtl());
    
    // Step 5: Verify redirect URL is correct
    $urlParts = parse_url($_SERVER['REQUEST_URI']);
    $redirectUrl = $urlParts['path'];
    $this->assertEquals('/blog/my-post', $redirectUrl);
}
```

#### Test 4.2: Language Switches Persist
```php
public function testLanguageSwitchPersistsAcrossRequests()
{
    // First request - set locale
    $_SESSION['scriptlog_locale'] = 'fr';
    $_COOKIE['scriptlog_locale'] = 'fr';
    
    // Second request - detect locale from session
    // (Simulate new request context)
    $detectedLocale = $_SESSION['scriptlog_locale'] ?? 
                      $_COOKIE['scriptlog_locale'] ?? 
                      'en';
    
    $this->assertEquals('fr', $detectedLocale);
}
```

---

## Test Implementation Order

### Priority 1: Core Logic Tests
1. `testSwitchLangParameterIsProcessed` - Basic parameter handling
2. `testRedirectUrlIsProperlyConstructed` - URL manipulation
3. `testLocaleIsStoredInSession` - Session storage
4. `testLocaleIsStoredInCookie` - Cookie storage

### Priority 2: Content Filtering Tests
5. `testPostsFilteredByLocale` - Post content filtering
6. `testTopicsFilteredByLocale` - Topic filtering
7. `testMenuItemsFilteredByLocale` - Menu filtering

### Priority 3: UI/Presentation Tests
8. `testRTLIsDetectedForArabic` - RTL detection
9. `testHtmlDirAttributeIsSetCorrectly` - HTML attribute
10. `testLanguageSwitcherDisplaysCorrectLanguage` - UI verification

### Priority 4: End-to-End Tests
11. `testCompleteLanguageSwitchFlow` - Full flow
12. `testLanguageSwitchPersistsAcrossRequests` - Persistence
13. `testInvalidLocaleIsRejected` - Error handling
14. `testDefaultLocaleFallbackWorks` - Fallback

---

## Test Data Requirements

### Required Test Data in Database

```sql
-- Languages
INSERT INTO tbl_languages (lang_code, lang_name, lang_native, lang_direction) VALUES
('en', 'English', 'English', 'ltr'),
('ar', 'Arabic', 'العربية', 'rtl'),
('zh', 'Chinese', '中文', 'ltr'),
('fr', 'French', 'Français', 'ltr');

-- Posts (with locales)
INSERT INTO tbl_posts (post_title, post_locale, post_status) VALUES
('English Post', 'en', 'publish'),
('Arabic Post', 'ar', 'publish'),
('Chinese Post', 'zh', 'publish');

-- Topics (with locales)
INSERT INTO tbl_topics (topic_title, topic_locale, topic_status) VALUES
('English Category', 'en', 'Y'),
('Arabic Category', 'ar', 'Y');

-- Menu items (with locales)
INSERT INTO tbl_menu (menu_label, menu_locale, menu_status) VALUES
('Home', 'en', 'Y'),
('الرئيسية', 'ar', 'Y');
```

---

## Test Execution Strategy

### Option A: HTTP Client Tests (Recommended)
Use Guzzle HTTP client to make real requests:

```php
public function testLiveLanguageSwitch()
{
    $client = new GuzzleHttp\Client(['base_uri' => 'http://localhost']);
    
    // Click Arabic language
    $response = $client->get('/?switch-lang=ar');
    
    // Follow redirect
    $this->assertEquals(302, $response->getStatusCode());
    $location = $response->getHeader('Location')[0];
    
    // Verify redirect to original URL
    $this->assertEquals('/', $location);
    
    // Make request to page with Arabic locale cookie
    $response = $client->get('/', [
        'cookies' => $cookies
    ]);
    
    // Verify content is in Arabic
    $body = (string) $response->getBody();
    $this->assertStringContainsString('العربية', $body);
}
```

### Option B: Simulated Request Tests
Simulate the request/response cycle without HTTP:

```php
public function testSimulatedLanguageSwitch()
{
    // Simulate environment
    $_SERVER['REQUEST_URI'] = '/blog/my-post';
    $_GET['switch-lang'] = 'ar';
    $_GET['redirect'] = '/blog/my-post';
    
    // Process through main.php logic (without header output)
    ob_start();
    // Include only the relevant logic, not full main.php
    $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($_GET['switch-lang']));
    $_SESSION['scriptlog_locale'] = $langCode;
    setcookie('scriptlog_locale', $langCode, time() + 86400, '/');
    ob_end_clean();
    
    // Verify results
    $this->assertEquals('ar', $_SESSION['scriptlog_locale']);
    $this->assertEquals('ar', $_COOKIE['scriptlog_locale']);
}
```

### Option C: Browser Automation (Selenium/Playwright)
For full end-to-end testing with JavaScript:

```php
public function testBrowserLanguageSwitch()
{
    $driver = SeleniumDriver::chrome();
    
    $driver->get('http://localhost/');
    
    // Click language dropdown
    $driver->findElement(WebDriverBy::cssSelector('#languageMenu'))
           ->click();
    
    // Click Arabic
    $driver->findElement(WebDriverBy::linkText('العربية'))
           ->click();
    
    // Wait for redirect
    $driver->wait()->until(WebDriverExpectedCondition::urlContains('/'));
    
    // Verify locale
    $html = $driver->getPageSource();
    $this->assertStringContainsString('dir="rtl"', $html);
}
```

---

## Files to Create/Modify

### New Files
| File | Purpose |
|------|---------|
| `tests/integration/LanguageSwitcherIntegrationTest.php` | Main integration test file |
| `tests/fixtures/language_switcher_fixture.php` | Test data fixtures |

### Modified Files
| File | Change |
|------|--------|
| `tests/bootstrap.php` | Add integration test bootstrap |
| `dev-docs/I18N_TESTING_GUIDE.md` | Update with integration tests |

---

## Estimated Implementation Time

| Phase | Tests | Estimated Time |
|-------|-------|---------------|
| Phase 1: HTTP Integration | 4 tests | 2 hours |
| Phase 2: Database Integration | 3 tests | 3 hours |
| Phase 3: RTL Integration | 2 tests | 1 hour |
| Phase 4: End-to-End | 4 tests | 4 hours |
| Documentation | - | 1 hour |
| **Total** | **13 tests** | **~11 hours** |

---

## Success Criteria

All integration tests must pass:

1. ✅ Switch-lang parameter is correctly processed
2. ✅ Session and cookie are set with locale
3. ✅ Redirect URL is correct
4. ✅ Content is filtered by locale (posts, topics, menus)
5. ✅ RTL layout is applied for Arabic
6. ✅ Language persists across page requests
7. ✅ Invalid locales are rejected
8. ✅ Default locale fallback works

---

## Next Steps

1. **Approve this plan** - Review and approve implementation approach
2. **Create Phase 1 tests** - Start with HTTP integration tests
3. **Set up test database** - Add required test data
4. **Run and iterate** - Execute tests, fix failures, expand coverage
5. **Document results** - Update testing guide with passing tests

---

**Document Version:** 1.0  
**Author:** Senior Fullstack Engineer  
**Date:** April 2026
