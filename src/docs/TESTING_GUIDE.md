# Testing Guide - Scriptlog

**Version:** 1.0.0 | **Last Updated:** April 2026

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
| **Total Tests** | 1,172 |
| **Test Files** | 73 |
| **Assertions** | ~1300+ |
| **PHPUnit Version** | 9.6.34 |
| **Target Coverage** | 40% |

### Test Categories

| Category | Description |
|----------|-------------|
| **Unit Tests** | Utility function tests, class existence tests |
| **Integration Tests** | Database CRUD operations using `blogware_test` database |

---

## 3. Test Coverage Plan

### Current State

| Category | Files/Tests |
|----------|-------------|
| Core classes | ~90 |
| DAO classes | 16 |
| Service classes | 16 |
| Controller classes | 15 |
| Model classes | 9 |
| Utility functions | ~196 |
| **Total PHP Files** | **339** |

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

| Phase | Priority | Status | New Tests | Cumulative |
|-------|----------|--------|-----------|------------|
| Phase 1: DAO Integration | HIGH | ✅ Complete | 92 | 92 |
| Phase 2: Service Layer | HIGH | ✅ Complete | 148 | 240 |
| Phase 3: Core Classes | MEDIUM | 🔄 Pending | 65 | 305 |
| Phase 4: Controllers | MEDIUM | 🔄 Pending | 34 | 339 |
| Phase 5: Utilities | LOW | 🔄 Complete | 68 | 407 |
| Password Protected Posts | HIGH | ✅ Complete | 59 | 466 |

**Total Completed**: 407 tests
**Current Total**: 1,172 tests

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

### Files to Create

- `tests/integration/CommentDaoIntegrationTest.php`
- `tests/service/PostServiceTest.php`
- `tests/core/AuthenticationTest.php`
- `tests/core/SessionMakerTest.php`
- `tests/core/FormValidatorTest.php`

### Recently Created Tests (contributing to 40% coverage goal)

#### Phase 1 - DAO Integration Tests (Complete)
- ✓ `tests/integration/UserDaoIntegrationTest.php`
- ✓ `tests/integration/PostDaoIntegrationTest.php` (Includes performance/eager loading tests)
- ✓ `tests/integration/PostDaoMethodIntegrationTest.php`
- ✓ `tests/integration/TopicDaoIntegrationTest.php`
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

#### Unit Tests
- ✓ `tests/unit/DownloadTest.php` (DownloadHandler, DownloadSettings, DownloadService)
- ✓ `tests/unit/PostControllerValidationTest.php` (validation unit tests)
- ✓ `tests/unit/PageCacheTest.php` (Cache utility unit tests)
- ✓ `tests/unit/MedooinFunctionsTest.php` (26 tests - database utility functions)
- ✓ `tests/unit/MembershipFunctionsTest.php` (26 tests - membership utility functions)
- ✓ `tests/unit/ImageDisplayTest.php` (image display function tests)
- ✓ `tests/unit/TranslationLoaderTest.php` (translation loader tests)
- ✓ `tests/unit/ThemeI18nTest.php` (theme i18n tests)
- ✓ `tests/unit/ConfigFileGenerationTest.php` (config file generation tests)
- ✓ `tests/unit/InstallationTest.php` (installation utility tests)
- ✓ `tests/unit/ImportUtilitiesTest.php` (import utility tests)
- ✓ `tests/unit/PerformanceOptimizationTest.php` (performance optimization tests)
- ✓ `tests/unit/LocaleRouterTest.php` (locale routing tests)
- ✓ `tests/unit/LocaleDetectorTest.php` (locale detection tests)
- ✓ `tests/unit/I18nManagerTest.php` (i18n manager tests)

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
        - lib/vendor/
        - lib/core/HTMLPurifier/
    level: 0
```

### Key Settings

- **phpVersion**: Set to `70400` for PHP 7.4 compatibility
- **level**: Currently at level 0 (most lenient). Increase gradually for stricter checks.
- **excludePaths**: Excludes vendor and third-party code

---

## 5. Running Tests

### PHPUnit Commands

```bash
# Run all tests
lib/vendor/bin/phpunit

# Run with coverage (requires Xdebug)
lib/vendor/bin/phpunit --coverage-html coverage

# Run specific test file
lib/vendor/bin/phpunit tests/EmailValidationTest.php

# Run tests matching pattern
lib/vendor/bin/phpunit --filter "EmailValidation"
```

### PHPStan Commands

```bash
# Run static analysis
lib/vendor/bin/phpstan analyse

# Run with specific config
lib/vendor/bin/phpstan analyse --configuration=phpstan.neon

# Run with memory limit (recommended)
lib/vendor/bin/phpstan analyse --memory-limit=1G

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

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash
lib/vendor/bin/phpstan analyse --memory-limit=1G
lib/vendor/bin/phpunit
```

---

## Troubleshooting

### PHPUnit Issues

| Issue | Solution |
|-------|----------|
| Tests fail with "Database not found" | Run `php tests/setup_test_db.php` |
| Xdebug required for coverage | Install Xdebug or skip coverage |

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

*Last Updated: April 2026 | Version 1.0.0*
