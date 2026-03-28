# Scriptlog

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.6%2B-4479A1.svg)](https://www.mysql.com/)
[![MariaDB Version](https://img.shields.io/badge/MariaDB-10.3%2B-003545.svg)](https://mariadb.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12-Compliant-2C2C2C.svg)](https://www.php-fig.org/psr/psr-12/)
[![Tests](https://img.shields.io/badge/Tests-633-44B273.svg)](https://phpunit.de/)

Scriptlog is a simple, secure, modular, and robust personal blogging platform. It is a refactored fork of Piluscart 1.4.1, engineered to emphasize simplicity, privacy, and security without the overhead of a complex Content Management System.

## Project Overview

Scriptlog is not designed to replace full-scale CMS frameworks. Instead, it is meticulously engineered to:
- Power personal weblogs that do not require a heavy CMS.
- Provide a secure foundation for blogging with modern security practices.
- Run fast with minimal overhead.

### Core Technologies
- **Backend:** PHP 7.4+ (PSR-12 compliant)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Architecture:** Multi-layered MVC-like (`Request` → `Controller` → `Service` → `DAO` → `Database`)
- **Security:** Laminas (Escaper, Crypt), Defuse PHP Encryption, voku Anti-XSS, HTMLPurifier.

## Requirements

Ensure your hosting environment meets the following requirements:
- **PHP:** 7.4.33 or higher
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx
- **Database:** MySQL 5.6+ or MariaDB 10.3+
- **Extensions:** `pdo`, `mysqli`, `curl`, `gd`, `mbstring`, `fileinfo`, `zip`, `exif`

## Installation

1. **Download & Extract**
   Unzip the package into your web root directory.

2. **Install Dependencies**
   Scriptlog uses Composer for dependency management.
   ```bash
   composer install
   ```

3. **Set Permissions**
   Ensure the following directories are writeable by the web server user:
   - `install/`
   - `public/log/`
   - `public/cache/`
   - `public/themes/`
   - `admin/plugins/` (if present)

4. **Database Setup**
   Create a new empty database (use `utf8mb4_general_ci` collation).

5. **Run the Installer**
   Navigate to `/install/` in your web browser (e.g., `http://your-site.com/install/`) and follow the wizard:
   - Requirement Check
   - Database Configuration
   - Administrator Account Setup

6. **Cleanup (Critical)**
   For security purposes, **delete the `install/` directory** immediately after installation is complete.

## Configuration

If the installer cannot write the configuration file, rename `config.sample.php` to `config.php` and update it manually:

```php
return [
    'db' => [
         'host' => 'localhost',
         'user' => 'your_db_user',
         'pass' => 'your_db_password',
         'name' => 'your_db_name'
      ],
    'app' => [
         'url'   => 'http://your-site.com',
         'email' => 'admin@example.com',
         'key'   => 'generated-app-key' 
     ]
];
```

## Directory Structure

- `admin/`: Administrator panel logic and UI.
- `lib/`: Core application logic (Controllers, Services, DAOs).
- `public/`: Web root for assets, themes, and user uploads.
  - `themes/`: Frontend templates.
  - `files/`: User uploads.
- `tests/`: PHPUnit test suite.
- `docs/`: Developer guides and API documentation.

## Development

Scriptlog adheres to **PSR-12** coding standards and uses **Conventional Commits**.

### Key Commands
- **Run Tests:** `vendor/bin/phpunit`
- **Setup Test DB:** `php tests/setup_test_db.php`

### Architecture Pattern
When adding features, follow the layered implementation pattern:
1. **DAO:** `lib/dao/` (Database interactions)
2. **Service:** `lib/service/` (Business logic)
3. **Controller:** `lib/controller/` (Request handling)

## Security Features

- **Authentication:** Custom secure session handler (`SessionMaker`).
- **CSRF:** Protected via `CSRFGuard` and `csrf_defender`.
- **XSS:** Multi-layered prevention using `Anti-XSS` and `HTMLPurifier`.
- **Encryption:** Sensitive data encrypted using `defuse/php-encryption`.

## Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

## Code of Conduct

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) to keep our community approachable and respectable.

## Security

For security vulnerabilities, please read our [Security Policy](SECURITY.md) for responsible disclosure guidelines.

## License

Scriptlog is Open Source and Free PHP Blog Software licensed under the [MIT License](LICENSE.md).

---

*Thank you for creating with Scriptlog.*
