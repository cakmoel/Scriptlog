# Testing Guide - Scriptlog

**Version:** 1.3.0 | **Last Updated:** August 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [PHPUnit Testing](#2-phpunit-testing)
3. [Test Coverage Plan](#3-test-coverage-plan)
4. [Static Analysis with PHPStan](#4-static-analysis-with-phpstan)
5. [Running Tests](#5-running-tests)
6. [Test Database Setup](#6-test-database-setup)
7. [Writing Tests](#7-writing-tests)
8. [CI/CD Integration](#8-cicd-integration)

---

## 1. Overview

This project uses two complementary testing approaches:

| Tool | Purpose | Coverage |
|------|---------|----------|
| **PHPUnit** | Unit and integration testing | Functional correctness |
| **PHPStan** | Static code analysis | Type safety, code quality |

---

## 2. PHPUnit Testing

### Test Suite Metrics

| Metric | Value |
|--------|-------|
| **Total Tests** | 2,269 |
| **Test Files** | 167 |
| **Assertions** | 8,074 |
| **Skipped / Risky** | 15 skipped, 60 risky |
| **PHPUnit Version** | 9.6.35 |
| **Target Coverage** | 40% |
| **Current Coverage** | ~38% |

### Test Categories

| Category | Description |
|----------|-------------|
| **Unit Tests** | Utility function tests, class existence tests |
| **Integration Tests** | Database CRUD operations using `blogware_test` database |
| **Service Tests** | Business-logic tests for `lib/service/` classes |
| **API Tests** | REST API tests under `tests/api/`, run via the dedicated API Tests suite |

---

## 3. Test Coverage Plan

### Current State

| Category | Files |
|----------|-------|
| Core classes (`lib/core`, excl. HTMLPurifier) | 103 |
| DAO classes | 19 |
| Service classes | 23 |
| Controller classes (incl. 12 API) | 32 |
| Handler classes (incl. admin commands) | 48 |
| Model classes | 9 |
| Validator classes | 5 |
| DTO classes (incl. `dto/api/`) | 5 |
| Utility function files | 224 |

### Test-Scenario Plan to Reach 40%

#### Phase 1: Expand Integration Tests (HIGH)
Add deep coverage for all DAO methods.

| Area | Tests |
|------|-------|
| UserDao | 15 |
| PostDao | 15 |
| TopicDao | 10 |
| CommentDao | 10 |
| MediaDao | 10 |
| PageDao | 8 |
| MenuDao | 6 |
| PluginDao | 6 |
| ThemeDao | 6 |
| ConfigurationDao | 6 |
| **Phase 1 Total** | **92** |

#### Phase 2: Service Layer Tests (HIGH)
Test business logic in Service classes.

| Area | Tests |
|------|-------|
| UserService | 15 |
| PostService | 12 |
| TopicService | 8 |
| CommentService | 10 |
| MediaService | 8 |
| ConfigurationService | 6 |
| ThemeService | 6 |
| PluginService | 6 |
| DownloadService | 4 |
| NotificationService | 13 |
| **Phase 2 Total** | **88** |

#### Phase 3: Core Class Tests (MEDIUM)
Test critical core functionality.

| Area | Tests |
|------|-------|
| Authentication | 10 |
| SessionMaker | 8 |
| FormValidator | 15 |
| Paginator | 8 |
| Sanitize | 8 |
| DbFactory | 4 |
| Dispatcher | 6 |
| View | 6 |
| **Phase 3 Total** | **65** |

#### Phase 4: Controller Tests (MEDIUM)
Test HTTP request handling.

| Area | Tests |
|------|-------|
| PostController | 8 |
| UserController | 8 |
| CommentController | 6 |
| MediaController | 6 |
| TopicController | 6 |
| **Phase 4 Total** | **34** |

#### Phase 5: Additional Utility Coverage (LOW)
Fill gaps in utility function testing.

| Area | Tests |
|------|-------|
| Upload utilities | 8 |
| Security utilities | 6 |
| Email utilities | 4 |
| Session utilities | 4 |
| Cache utilities | 4 |
| **Phase 5 Total** | **26** |

### Implementation Summary

| Phase | Priority | Status | New Tests | Cumulative Total |
|-------|----------|--------|-----------|----------|
| Pre-existing | - | ✅ Complete | 833 | 833 |
| Phase 1: DAO Integration | HIGH | ✅ Complete | 92 | 925 |
| Phase 2: Service Layer | HIGH | ✅ Complete | 148 | 1,073 |
| Phase 3: Core Classes | MEDIUM | ✅ Complete | 65 | 1,138 |
| Phase 4: Controllers | MEDIUM | ✅ Complete | 34 | 1,172 |
| Phase 5: Utilities | LOW | ✅ Complete | 68 | 1,240 |
| Password Protected Posts | HIGH | ✅ Complete | 59 | 466 |

**Total New Tests Added**: 466
**Total Suite**: 2,269 tests across 167 files, 8,074 assertions

> The phase table above is a historical record of when tests were added. Current suite totals as of August 2026: 2,269 tests across 167 files with 8,074 assertions (15 skipped, 60 risky).

### Recently Added Tests

#### Medoo and Membership Utilities Tests (April 2026)
- ✓ `tests/unit/MedooinFunctionsTest.php` (26 tests)
  - Tests for `is_medoo_database()`, `is_db_database()`, `db_build_where()`
  - Tests for `medoo_select()`, `medoo_insert()`, `medoo_update()`, `medoo_delete()`
  - Tests for PDO::FETCH_ASSOC return format compatibility

- ✓ `tests/integration/MedooinIntegrationTest.php` (8+ tests)
  - Integration tests for database selection and operations
  - Tests for table prefix handling

- ✓ `tests/unit/MembershipFunctionsTest.php` (26 tests)
  - Tests for `is_registration_unable()`, `membership_default_role()`
  - Tests for `membership_get_role()`, `membership_get_role_name()`
  - Tests for registration role and user level mappings

- ✓ `tests/integration/MembershipIntegrationTest.php` (8 tests)
  - Integration tests for membership settings and role configuration

#### PostDao Security Tests (April 2026)
- ✓ `tests/unit/PostDaoSecurityTest.php` (6 tests)
  - Verifies onlyPublished parameter defaults to true in findPosts()
  - Verifies onlyPublished parameter defaults to true in findPost()
  - Verifies author parameter is properly defined
  - Verifies ORDER BY column whitelist prevents SQL injection
  - Verifies status filter: post_status = 'publish'
  - Verifies visibility filter: post_visibility = 'public'

### All Planned Test Files Created ✓

### Recently Created Tests (contributing to 40% coverage goal)

#### Phase 1 - DAO Integration Tests (Complete)
- ✓ `tests/integration/UserDaoIntegrationTest.php`
- ✓ `tests/integration/PostDaoIntegrationTest.php` (Includes performance/eager loading tests)
- ✓ `tests/integration/PostDaoMethodIntegrationTest.php`
- ✓ `tests/integration/TopicDaoTest.php`
- ✓ `tests/integration/PageDaoIntegrationTest.php`
- ✓ `tests/integration/MenuDaoIntegrationTest.php`
- ✓ `tests/integration/PluginDaoIntegrationTest.php`
- ✓ `tests/integration/ThemeDaoIntegrationTest.php`

#### Password-Protected Posts Tests (April 2026)

**Total: 59 tests across 3 files**

- ✓ `tests/unit/ProtectedPostTest.php` (12 tests)
  - Tests for `protect_post()`, `encrypt_post()`, `decrypt_post()`
  - Tests for `checking_post_password()`, `grab_post_protected()`
  - Visibility validation tests (public, private, protected)

- ✓ `tests/unit/ProtectedPostRateLimitTest.php` (20 tests)
  - Rate limiting logic tests (5 attempts limit per 15 minutes)
  - Old attempts expiration tests
  - Separate limits per post ID and IP
  - Password strength validation tests (length, uppercase, lowercase, number, special char)
  - Session-based unlock storage tests
  - Tests for: `is_unlock_rate_limited()`, `track_failed_unlock_attempt()`, `clear_failed_unlock_attempts()`, `get_failed_unlock_attempts()`, `check_post_password_strength()`

- ✓ `tests/unit/PostControllerProtectedPostTest.php` (27 tests)
  - Visibility validation tests (public, private, protected)
  - Password validation for protected posts
  - Content encryption/decryption flow
  - Session handling for protected posts
  - Form validation error handling
  - CSRF protection tests
  - Required field validation tests

#### Phase 2 - Service Layer Tests (Complete)
- ✓ `tests/service/UserServiceTest.php` (18 tests)
- ✓ `tests/service/PostServiceTest.php` (24 tests)
- ✓ `tests/service/TopicServiceTest.php` (7 tests)
- ✓ `tests/service/CommentServiceTest.php` (10 tests)
- ✓ `tests/service/MediaServiceTest.php` (16 tests)
- ✓ `tests/service/ConfigurationServiceTest.php` (10 tests)
- ✓ `tests/service/ThemeServiceTest.php` (10 tests)
- ✓ `tests/service/PluginServiceTest.php` (13 tests)
- ✓ `tests/service/MenuServiceTest.php` (14 tests)
- ✓ `tests/service/PageServiceTest.php` (16 tests)
- ✓ `tests/service/NotificationServiceTest.php` (14 tests)
- ✓ `tests/service/DataRequestServiceTest.php`
- ✓ `tests/service/ScheduledPostServiceTest.php`
- ✓ `tests/service/DownloadCreateLinkTest.php`

#### Phase 3 - Core Class Tests (Complete) - 65 tests
Core tests live in `tests/core/` and `tests/unit/`. The dedicated `SessionMaker`, `Paginator`, `Sanitize`, `DbFactory`, and `View` test files were later removed during test consolidation; those classes are still exercised indirectly by integration and service tests.
- ✓ `tests/core/AuthenticationTest.php` (10 tests - user access control, cookie handling, login)
- ✓ `tests/core/FormValidatorTest.php` (15 tests - input validation, sanitization, JSON output)
- ✓ `tests/unit/DispatcherDispatchTest.php` (6 tests - URL routing, content validation, 404 handling)

#### Phase 4 - Controller Tests (Complete)

The original `tests/controller/*` files were consolidated into `tests/unit/` and `tests/api/unit/`. Current controller test files:

- ✓ `tests/unit/PostControllerProtectedPostTest.php` (27 tests)
- ✓ `tests/unit/PostControllerValidationTest.php`
- ✓ `tests/unit/SearchControllerTest.php`
- ✓ `tests/unit/ApiControllerTest.php`
- ✓ `tests/unit/QueryApiControllerTest.php`
- ✓ `tests/unit/SearchApiControllerTest.php`
- ✓ `tests/api/unit/*ApiControllerTest.php` (10 API endpoint controller tests)

#### Unit Tests (60+ files)
- ✓ `tests/unit/AdminLocaleInitializationTest.php`
- ✓ `tests/unit/ApiHateoasTest.php`
- ✓ `tests/unit/ApiResponseTest.php`
- ✓ `tests/unit/AppKeyTest.php`
- ✓ `tests/unit/BootstrapTest.php`
- ✓ `tests/unit/ConfigFileGenerationTest.php`
- ✓ `tests/unit/DownloadPageDataTest.php`
- ✓ `tests/unit/DownloadServiceTest.php`
- ✓ `tests/unit/DownloadSettingsTest.php`
- ✓ `tests/unit/DownloadUtilityTest.php`
- ✓ `tests/unit/FrontServiceTest.php`
- ✓ `tests/unit/GenerateOpenApiSpecTest.php`
- ✓ `tests/unit/GenerateRequestTest.php`
- ✓ `tests/unit/HandlerRegistryTest.php`
- ✓ `tests/unit/handlers/HandlerStructureTest.php`
- ✓ `tests/unit/handlers/PostHandlerTest.php`
- ✓ `tests/unit/I18nManagerTest.php`
- ✓ `tests/unit/ImageDisplayTest.php`
- ✓ `tests/unit/ImportUtilitiesTest.php`
- ✓ `tests/unit/InstallationTest.php`
- ✓ `tests/unit/LanguageSwitcherTest.php`
- ✓ `tests/unit/LocaleDetectorTest.php`
- ✓ `tests/unit/LocaleRouterTest.php`
- ✓ `tests/unit/MedooinFunctionsTest.php` (26 tests)
- ✓ `tests/unit/MembershipFunctionsTest.php` (26 tests)
- ✓ `tests/unit/NavigationI18nTest.php`
- ✓ `tests/unit/OpenApiSpecVerificationTest.php`
- ✓ `tests/unit/PageCacheTest.php`
- ✓ `tests/unit/PerformanceOptimizationTest.php`
- ✓ `tests/unit/PostControllerProtectedPostTest.php` (27 tests)
- ✓ `tests/unit/PostControllerValidationTest.php`
- ✓ `tests/unit/PostDaoSecurityTest.php`
- ✓ `tests/unit/PostDaoUpdateFixTest.php`
- ✓ `tests/unit/ProtectedPostRateLimitTest.php` (20 tests)
- ✓ `tests/unit/ProtectedPostTest.php` (12 tests)
- ✓ `tests/unit/RateLimiterTest.php`
- ✓ `tests/unit/ScriptlogCryptonizeTest.php`
- ✓ `tests/unit/SidebarNavigationTest.php`
- ✓ `tests/unit/ThemeI18nTest.php`
- ✓ `tests/unit/ThemeRendererTest.php`
- ✓ `tests/unit/ThemeUploadTest.php`
- ✓ `tests/unit/TranslationLoaderTest.php`

Additional unit tests live under `tests/unit/handlers/`, `tests/unit/validator/`, and `tests/unit/dto/`. API tests are under `tests/api/unit/` and `tests/api/integration/` (plus `tests/api/unit/dto/`), and a smoke test under `tests/smoke/`.

## 4. Static Analysis with PHPStan

PHPStan is a static analysis tool that finds bugs in your code without running it.

### Configuration Files

| File | Purpose |
|------|---------|
| `phpstan.neon` | Main configuration |
| `phpstan.baseline.neon` | Baseline of known issues to ignore |

### PHPStan Configuration

```neon
includes:
    - phpstan.baseline.neon

parameters:
    phpVersion: 70400

    paths:
        - lib/
        - index.php

    excludePaths:
        - lib/vendor/*
        - lib/core/HTMLPurifier/*
        - lib/core/ServiceException.php
        - tests/*

    bootstrapFiles:
        - tests/phpstan-bootstrap.php

    reportUnmatchedIgnoredErrors: false

    level: 0
```

### Key Settings

- **phpVersion**: Set to `70400` for PHP 7.4 compatibility
- **level**: Currently at level 0 (most lenient). The `composer phpstan` script runs at level 1; `composer phpstan:strict` runs at level 5.
- **excludePaths**: Excludes vendor and third-party code (HTMLPurifier) plus the test suite itself
- **bootstrapFiles**: Loads `tests/phpstan-bootstrap.php` before analysis

---

## 5. Running Tests

### PHPUnit Commands

```bash
# Run all tests (uses phpunit.xml: 5 test suites)
lib/vendor/bin/phpunit

# PHP 8.5 dev environment emits deprecation noise; suppress for readable output:
php -d error_reporting='E_ALL' lib/vendor/bin/phpunit

# Or via Composer
composer test

# Run with coverage (requires Xdebug)
lib/vendor/bin/phpunit --coverage-html coverage

# Run specific test file
lib/vendor/bin/phpunit tests/EmailValidationTest.php

# Run tests matching pattern
lib/vendor/bin/phpunit --filter "EmailValidation"
```

### PHPStan Commands

```bash
# Run static analysis (level 1 via composer)
composer phpstan

# Run strict analysis (level 5)
composer phpstan:strict

# Run with memory limit (recommended)
lib/vendor/bin/phpstan analyse --memory-limit=1G

# Run with specific config
lib/vendor/bin/phpstan analyse --configuration=phpstan.neon

# Generate/update baseline
lib/vendor/bin/phpstan analyse --generate-baseline=phpstan.baseline.neon

# Increase analysis level for stricter checks
lib/vendor/bin/phpstan analyse -l 5
```

### Combined Run Script

Create a script to run both:

```bash
#!/bin/bash
echo "Running PHPUnit tests..."
lib/vendor/bin/phpunit

echo ""
echo "Running PHPStan static analysis..."
lib/vendor/bin/phpstan analyse --memory-limit=1G
```

---

## 6. Test Database Setup

### Create Test Database

```bash
# Create test database
php tests/setup_test_db.php

# Or manually
mysql -u root -p -e "CREATE DATABASE blogware_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

### Test Database Configuration

Tests use a separate database (`blogware_test`) to avoid affecting production data.

---

## 7. Writing Tests

### PHPUnit Test Structure

```php
<?php
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
        $this->assertIsString('test');
    }
    
    public function testWithFunction(): void
    {
        if (function_exists('some_function')) {
            $result = some_function('input');
            $this->assertIsString($result);
        }
    }
}
```

### Best Practices

1. **Test one thing per method** - Each test should verify a single behavior
2. **Use descriptive names** - Method names should describe what is being tested
3. **Arrange-Act-Assert** - Structure tests with clear setup, action, and verification phases
4. **Mock external dependencies** - Use mocks for database, filesystem, etc.

### PHPStan Best Practices

1. **Fix errors incrementally** - Start with level 0, then increase gradually
2. **Update baseline regularly** - Run with `--generate-baseline` after significant changes
3. **Add type hints** - Improves both PHPStan analysis and code readability
4. **Document exceptions** - Use `@throws` PHPDoc tags for exceptions

---

## 8. CI/CD Integration

### GitHub Actions Example

```yaml
name: Test

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
       
      - name: Install dependencies
        run: composer install --no-interaction --no-dev
       
      - name: Run PHPUnit
        run: lib/vendor/bin/phpunit
       
      - name: Run PHPStan
        run: lib/vendor/bin/phpstan analyse --memory-limit=1G
```

### Pre-commit Hook

The repository ships a pre-commit hook at `.githooks/pre-commit`. Enable it after clone:

```bash
git config core.hooksPath .githooks
```

The hook enforces:

- No `.sql` files or unexpected non-directory files under `lib/` (SQL belongs in `install/`)
- PHP 7.4 backward compatibility via the PHPCompatibility sniff (`phpcs-compatibility.xml`) on every staged PHP file
- It blocks commits that use PHP 8.0+ features or rely on changed defaults (e.g. `html_entity_decode()` flags changed in PHP 8.1)

Do not bypass with `git commit --no-verify`.

---

## Troubleshooting

### PHPUnit Issues

| Issue | Solution |
|-------|----------|
| Tests fail with "Database not found" | Run `php tests/setup_test_db.php`, or create the `blogware_test` database manually |
| Integration tests skip unexpectedly | Ensure `Registry::set('dbc', ...)` is called in setUpBeforeClass for DAO-dependent tests |
| Xdebug required for coverage | Install Xdebug or skip coverage with `--no-coverage` |
| DAO locale/lang_code too long | Keep test locale values ≤ 10 chars for VARCHAR(10) columns |
| PHPUnit deprecation noise on PHP 8.5 | Run with `php -d error_reporting='E_ALL' lib/vendor/bin/phpunit` |

### PHPStan Issues

| Issue | Solution |
|-------|----------|
| Memory limit exceeded | Run with `--memory-limit=1G` |
| Too many errors | Use baseline or increase level gradually |
| False positives | Add to ignoreErrors in phpstan.neon |
| Missing bleedingEdge.neon | Remove from includes in phpstan.neon |

---

## Additional Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Configuration Reference](https://phpstan.org/config-reference)

---

*Last Updated: August 2026 | Version 1.3.0*
