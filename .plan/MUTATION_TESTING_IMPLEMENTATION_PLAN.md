# Mutation Testing Implementation Plan

**Version:** 1.0 | **Created:** April 2026 | **PHP Compatibility:** >= 7.4

---

## 1. Executive Summary

This plan outlines the implementation of **mutation testing** for Blogware/Scriptlog using **Infection 0.26.x** (PHP 7.4 compatible). The project maintains PHP 7.4+ backward compatibility while leveraging mutation testing to improve test quality.

### Current State

| Metric | Value |
|--------|-------|
| **PHP Runtime** | 8.3.30 |
| **PHP Platform Config** | 7.4.33 (unchanged) |
| **PHPUnit Version** | 9.6.34 |
| **Xdebug Version** | 3.5.0 |
| **Total Tests** | 62 passing assertions |
| **Test Files** | 70 files |
| **Infection Version** | 0.26.6 (PHP 7.4 compatible) |

---

## 2. PHP Version Compatibility

### Infection Version Selection

| PHP Version | Infection Version | Selected |
|-------------|-------------------|----------|
| 8.2.0+ | >= 0.29.10 | ❌ |
| 8.1.0+ | >= 0.26.16 | ❌ |
| **7.4.0+** | **>= 0.18, <= 0.26.6** | **✅ 0.26.6** |
| 7.3.12 | 0.16-0.17 | ❌ |

**Decision:** Use **Infection 0.26.6** - latest version compatible with PHP 7.4+.

### Platform Configuration

**NO CHANGE** to `composer.json` platform config:
```json
"config": {
    "platform": {
        "php": "7.4.33"
    }
}
```

The project remains PHP 7.4+ backwards compatible.

---

## 3. Installation

### 3.1 Install Infection 0.26.6

```bash
cd /var/www/blogware/public_html
composer require --dev infection/infection:^0.26 --no-update
composer update infection/infection --with-dependencies
```

### 3.2 Verify Installation

```bash
php lib/vendor/bin/infection --version
```

---

## 4. Configuration

### 4.1 Create `infection.json`

```json
{
    "source": {
        "directories": [
            "lib"
        ],
        "excludes": [
            "lib/vendor",
            "lib/core/HTMLPurifier",
            "lib/utility/db-mysqli.php"
        ]
    },
    "logs": {
        "text": "infection/output/infection-log.txt",
        "summary": "infection/output/summary.txt",
        "json": "infection/output/infection.json",
        "html": "infection/output/html"
    },
    "mutators": {
        "@default": true
    },
    "testFramework": "phpunit",
    "testFrameworkOptions": "--configuration=phpunit.xml",
    "coverage": {
        "path": "infection/coverage"
    },
    "minMsi": 30,
    "minCoveredMsi": 40,
    "ignoreMsiWithNoMutations": true
}
```

### 4.2 Create Output Directory

```bash
mkdir -p infection/output
```

---

## 5. Implementation Phases

### Phase 1: Setup & Baseline (Week 1)

| Task | Description | Priority |
|------|-------------|----------|
| Install Infection 0.26.6 | `composer require --dev infection/infection:^0.26` | HIGH |
| Create infection.json | Configure source, logs, mutators | HIGH |
| Create output directory | `mkdir -p infection/output` | HIGH |
| Run initial baseline | `php lib/vendor/bin/infection --threads=$(nproc)` | HIGH |
| Document baseline results | Record MSI, escaped mutants, killed mutants | HIGH |

**Deliverable:** Baseline mutation score report

### Phase 2: Fix Low-Hanging Fruit (Week 2)

| Task | Description | Priority |
|------|-------------|----------|
| Review escaped mutants | Identify tests that should have caught mutations | HIGH |
| Add missing assertions | Strengthen tests that pass but shouldn't | MEDIUM |
| Target utility functions | Focus on `lib/utility/*.php` first | MEDIUM |
| Target DAO methods | Focus on `lib/dao/*.php` | MEDIUM |

**Deliverable:** Improved mutation score by 10-15%

### Phase 3: Service Layer Coverage (Week 3)

| Task | Description | Priority |
|------|-------------|----------|
| Target service methods | `lib/service/*.php` | HIGH |
| Add edge case tests | Boundary conditions, null handling | MEDIUM |
| Test error paths | Exception handling, validation failures | MEDIUM |

**Deliverable:** Service layer mutation score > 40%

### Phase 4: Controller & Core (Week 4)

| Task | Description | Priority |
|------|-------------|----------|
| Target controller methods | `lib/controller/*.php` | MEDIUM |
| Target core classes | `lib/core/*.php` | MEDIUM |
| Refactor untestable code | Extract pure functions from complex methods | LOW |

**Deliverable:** Overall MSI > 30%

---

## 6. Mutator Categories (Infection 0.26.x)

### 6.1 Arithmetic Mutators

| Mutator | Example |
|---------|---------|
| `Plus` | `$a + $b` → `$a - $b` |
| `Minus` | `$a - $b` → `$a + $b` |
| `Multiply` | `$a * $b` → `$a / $b` |
| `Division` | `$a / $b` → `$a * $b` |
| `Modulus` | `$a % $b` → `$a * $b` |

### 6.2 Comparison Mutators

| Mutator | Example |
|---------|---------|
| `Equal` | `$a == $b` → `$a != $b` |
| `NotEqual` | `$a != $b` → `$a == $b` |
| `Identical` | `$a === $b` → `$a !== $b` |
| `NotIdentical` | `$a !== $b` → `$a === $b` |
| `LessThan` | `$a < $b` → `$a <= $b` |
| `GreaterThan` | `$a > $b` → `$a >= $b` |

### 6.3 Logical Mutators

| Mutator | Example |
|---------|---------|
| `LogicalAnd` | `$a && $b` → `$a \|\| $b` |
| `LogicalOr` | `$a \|\| $b` → `$a && $b` |
| `LogicalNot` | `!$a` → `$a` |

### 6.4 Conditional Mutators

| Mutator | Example |
|---------|---------|
| `TrueValue` | `true` → `false` |
| `FalseValue` | `false` → `true` |
| `NullValue` | `null` → `""` |
| `ArrayItem` | `$arr[0]` → `$arr[1]` |
| `Concat` | `$a . $b` → `""` |

### 6.5 Function Call Mutators

| Mutator | Example |
|---------|---------|
| `FunctionCallRemoval` | `trim($a)` → `$a` |
| `MethodCallRemoval` | `$obj->method()` → (removed) |

### 6.6 Return Value Mutators

| Mutator | Example |
|---------|---------|
| `ReturnValue` | `return true` → `return false` |
| `YieldValue` | `yield $a` → `yield null` |

---

## 7. Target Files (Priority Order)

### High Priority (Start Here)

| File | Reason |
|------|--------|
| `lib/utility/protected-post.php` | Critical security - password handling |
| `lib/utility/encrypt-decrypt.php` | Critical security - encryption |
| `lib/utility/email-validation.php` | Input validation |
| `lib/utility/check-password.php` | Password strength checks |
| `lib/service/PostService.php` | Core business logic |
| `lib/service/UserService.php` | User management |

### Medium Priority

| File | Reason |
|------|--------|
| `lib/dao/PostDao.php` | Data access |
| `lib/dao/UserDao.php` | Data access |
| `lib/controller/PostController.php` | Request handling |
| `lib/core/Authentication.php` | Authentication |
| `lib/core/Dispatcher.php` | Routing |

### Low Priority (Later)

| File | Reason |
|------|--------|
| `lib/core/HTMLPurifier/` | Third-party, excluded |
| `lib/vendor/` | Third-party, excluded |
| `lib/utility/db-mysqli.php` | Legacy, excluded |

---

## 8. Success Metrics

| Metric | Target | Current |
|--------|--------|---------|
| **MSI (Mutation Score Indicator)** | ≥ 30% | 0% (not measured) |
| **Covered MSI** | ≥ 40% | 0% (not measured) |
| **Killed Mutants** | ≥ 50% | 0% |
| **Escaped Mutants** | ≤ 30% | 100% |
| **Untested Code** | ≤ 20% | 100% |

---

## 9. CI/CD Integration

### 9.1 GitHub Actions Workflow

```yaml
name: Mutation Testing

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  mutation:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          coverage: xdebug
      - name: Install dependencies
        run: composer install --no-interaction
      - name: Run mutation testing
        run: php lib/vendor/bin/infection --threads=$(nproc) --min-msi=30
```

### 9.2 Pre-merge Gate

- Block PRs if MSI drops below 30%
- Report mutation score in PR comments

---

## 10. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Long execution time | High | Use `--threads=$(nproc)` |
| False positives | Medium | Review mutants manually |
| Untestable code | Medium | Refactor to extract pure functions |
| Third-party code | Low | Exclude from source directories |
| PHP 7.4 compatibility | High | Use Infection 0.26.x |

---

## 11. Timeline

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| Phase 1: Setup | 1 week | Baseline report |
| Phase 2: Quick Wins | 1 week | +10-15% MSI |
| Phase 3: Service Layer | 1 week | Service MSI > 40% |
| Phase 4: Controllers | 1 week | Overall MSI > 30% |

**Total Estimated Duration:** 4 weeks

---

## 12. Commands Reference

```bash
# Run all mutations
php lib/vendor/bin/infection --threads=$(nproc)

# Run with HTML report
php lib/vendor/bin/infection --threads=$(nproc) --show-mutations

# Run specific file
php lib/vendor/bin/infection --filter=lib/utility/protected-post.php

# Run with minimum MSI threshold
php lib/vendor/bin/infection --min-msi=30

# Run with coverage-only mode (faster)
php lib/vendor/bin/infection --only-covered

# Generate summary only
php lib/vendor/bin/infection --show-summary
```

---

*Last Updated: April 2026 | Version 1.0 | PHP Compatibility: >= 7.4*
