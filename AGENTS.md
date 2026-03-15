# AGENTS.md - Scriptlog Development Guide

Guidelines for agentic coding agents working on Scriptlog, a PHP-based blog engine.

## Project Overview

- **PHP**: 7.4+ | **Database**: MySQL 5.6+ with Medoo ORM | **Testing**: PHPUnit 9.x
- Main source: `src/`

---

## Commands

### Testing

```bash
# Run all tests
cd src && php vendor/bin/phpunit

# Run single test file
cd src && php vendor/bin/phpunit tests/UrlValidationTest.php

# Run specific test method
cd src && php vendor/bin/phpunit tests/UrlValidationTest.php --filter testUrlValidationWithValidUrl

# Testdox output
cd src && php vendor/bin/phpunit --testdox

# Integration tests only
cd src && php vendor/bin/phpunit tests/integration/
```

### Composer

```bash
cd src && composer install
cd src && composer dump-autoload
```

---

## Code Style

### File Structure

- PHP opening tag: `<?php` (no closing tag)
- Security guard in classes: `defined('SCRIPTLOG') || die("Direct access not permitted");`
- Encoding: UTF-8

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `UserDao`, `CSRFGuard` |
| Functions | snake_case | `app_url()`, `is_valid_domain()` |
| Variables | snake_case | `$user_login`, `$app_url` |
| Constants | UPPER_SNAKE_CASE | `APP_PROTOCOL` |
| Tables | `tbl_` prefix | `tbl_users` |

### Classes

```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * ClassName
 *
 * @category  Dao Class
 * @author    Author Name
 * @license   MIT
 * @version   1.0
 */
class ClassName extends ParentClass
{
    private $property;
    
    public function __construct()
    {
        parent::__construct();
    }
}
```

### Functions

```php
/**
 * function_name
 *
 * @category function
 * @author Author Name
 * @license MIT
 * @version 1.0
 * @param string $paramName
 * @return boolean|array|object
 */
function function_name($paramName)
{
    // ...
}
```

### Formatting

- Indentation: 4 spaces, Same-line braces
- SQL keywords: UPPERCASE
- Types: Use return types (PHP 7.4+): `function getUserById($userID): bool|array|object`

### Imports

```php
use Egulias\EmailValidator\Validation\RFCValidation;
```

### Error Handling

Return `false`/empty arrays on failure (not exceptions in utilities). Use `http_response_code()` for HTTP errors. Check existence before calling:

```php
function user_init_dao()
{
    return (class_exists('UserDao')) ? new UserDao() : "";
}
```

### Database

- Use Medoo ORM, prepared statements with placeholders
- Tables prefixed with `tbl_`
- Sanitize IDs with `$this->filteringId()`

### Security

- Always sanitize user input
- Use CSRF tokens: `csrf_generate_token()`, `csrf_check_token()`
- XSS protection: `remove_xss()`, `safe_html()`
- Email validation: RFCValidation, Password hashing: `scriptlog_password()`

---

## Testing

- Extend `PHPUnit\Framework\TestCase`
- File naming: `*Test.php`
- Method naming: `test*()`
- Use assertions: `$this->assertTrue()`, `$this->assertEquals()`

---

## Notes

- Legacy code being refactored; inconsistencies may exist
- Not production-ready
