# Scriptlog

**Empowering Your Personal Weblog**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20--%208.5-777BB4.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)](https://www.mysql.com/)
[![MariaDB Version](https://img.shields.io/badge/MariaDB-10.3%2B-003545.svg)](https://mariadb.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12-Compliant-2C2C2C.svg)](https://www.php-fig.org/psr/psr-12/)
[![Tests](https://github.com/cakmoel/Scriptlog/actions/workflows/tests.yml/badge.svg)](https://github.com/cakmoel/Scriptlog/actions/workflows/tests.yml)
![Scriptlog Mascot](assets/scriptlog-vector-logo.png)

---

Scriptlog is a free and open-source PHP blog software designed to be simple, private, and secure. It powers personal weblogs without the overhead of a full-scale Content Management System - a fast, minimal, and modular foundation for sharing your stories and thoughts.

## Project Overview

Scriptlog is not designed to replace full-scale CMS frameworks. Instead, it is meticulously engineered to:
- Power personal weblogs that do not require a heavy CMS.
- Provide a secure foundation for blogging with modern security practices.
- Run fast with minimal overhead.

### Core Technologies
- **Backend:** PHP 7.4 - 8.5 (PSR-12 compliant)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Architecture:** Multi-layered MVC-like (`Request` -> `Bootstrap` -> `Dispatcher` -> `Controller` -> `Service` -> `DAO` -> `Database`)
- **Security:** Laminas (Escaper, Crypt), Defuse PHP Encryption, voku Anti-XSS, HTMLPurifier.

## Requirements

Ensure your hosting environment meets the following requirements:
- **PHP:** 7.4 - 8.5 (with extensions: `pdo`, `pdo_mysql`, `mbstring`, `iconv`, `fileinfo`, `gd`, `curl`, `openssl`, `json`)
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Composer:** Latest (for dependency management)

## Installation

The application is served from the project root, which is your web root: `index.php` is the front controller and `admin/index.php` is the admin entry point. All commands below run from the repository root.

1. **Clone the Repository**
   ```bash
   git clone https://github.com/cakmoel/Scriptlog.git
   cd Scriptlog
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```
   > **Note:** Composer's `platform.php` config locks dependency resolution to PHP 7.4, ensuring all packages stay compatible across PHP 7.4 through 8.5. No runtime warnings occur on newer PHP versions.

3. **Set Permissions**
   ```bash
   # Writable directories the installer verifies (web server needs write access)
   chmod -R 775 public/cache public/log public/files public/themes admin/plugins
   ```
   > **Note:** Adjust ownership if needed - the web server user (e.g., `www-data`) must be able to write to `public/cache`, `public/log`, `public/files`, `public/themes`, and `admin/plugins`.

   After the installer finishes, restrict access to the generated configuration files:
   ```bash
   chmod 640 config.php .env
   ```

4. **Database Setup**
   Create a new empty database (use `utf8mb4_general_ci` collation).

5. **Run the Installer**
   Navigate to `/install/` in your web browser and follow the wizard:
   - Step 1: System Requirements Check (`install/index.php`)
   - Step 2: Database Setup (`install/setup-db.php`) - creates 23 tables
   - Step 3: Complete Setup (`install/finish.php`)

6. **Cleanup (Critical)**
   For security purposes, **delete the `install/` directory** immediately after installation is complete.

### Configuration Files

After installation, the following configuration files are generated:

| File | Purpose |
|------|---------|
| `config.php` | Main configuration with `$_ENV` fallbacks |
| `.env` | Environment variables (auto-generated) |
| `../storage/keys/[random_filename].php` | Defuse encryption key for authentication cookies (kept outside the web root) |

## Configuration

Scriptlog supports both `.env` and `config.php` files for configuration. During installation, both files are automatically generated in the project root and kept in sync.

### config.php Structure

```php
<?php
return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'user' => $_ENV['DB_USER'] ?? '',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'name' => $_ENV['DB_NAME'] ?? '',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'prefix' => $_ENV['DB_PREFIX'] ?? ''
    ],
    'app' => [
        'url'   => $_ENV['APP_URL'] ?? 'https://your-domain.com',
        'email' => $_ENV['APP_EMAIL'] ?? '',
        'key'   => $_ENV['APP_KEY'] ?? '',
        'defuse_key' => $_ENV['DEFUSE_KEY_PATH'] ?? '/var/www/your-project/storage/keys/[random_filename].php'
    ],
    'mail' => [
        'smtp' => [
            'host' => $_ENV['SMTP_HOST'] ?? '',
            'port' => $_ENV['SMTP_PORT'] ?? 587,
            'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
            'username' => $_ENV['SMTP_USER'] ?? '',
            'password' => $_ENV['SMTP_PASS'] ?? '',
        ],
        'from' => [
            'email' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Blogware'
        ]
    ],
    'os' => [
        'system_software' => $_ENV['SYSTEM_OS'] ?? 'Linux',
        'distrib_name'    => $_ENV['DISTRIB_NAME'] ?? 'Linux'
    ],
    'api' => [
        'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'https://your-domain.com'
    ],
];
```

## Running the Application

The project root is the web root (`index.php` is the front controller):

| Environment | URL |
|-------------|-----|
| **Public Site** | `http://your-domain/` |
| **Admin Panel** | `http://your-domain/admin/` |
| **API Endpoint** | `http://your-domain/api/v1/` |

## Directory Structure

```
Scriptlog/
|-- index.php                    # Public front controller
|-- config.php                   # Application configuration (generated by installer)
|-- .env                         # Environment variables (generated by installer)
|-- .htaccess                    # Apache rewrite and security rules
|-- robots.txt                   # Search engine directives
|-- rss.php / atom.php           # RSS and Atom feeds
|-- sitemap.php                  # XML sitemap
|-- readme.html                  # In-app readme (installation reference)
|-- assets/                      # Repository assets (mascot, graphics)
|
|-- admin/                       # Administration panel (admin/index.php)
|   |-- dashboard.php            # Dashboard
|   |-- posts.php                # Post management
|   |-- pages.php                # Page management
|   |-- users.php                # User management
|   |-- option-*.php             # Settings pages (general, mail, permalink, ...)
|   |-- plugins/                 # Installed plugins (each in its own folder)
|   |-- assets/                  # Admin assets
|   +-- ...                      # Other admin pages, UI assets, WYSIWYG editor
|
|-- api/                         # RESTful API entry point (/api/v1/)
|   +-- index.php
|
|-- install/                     # Installer wizard (DELETE after installation)
|   +-- include/                 # Installer helpers (dbtable.php, check-engine.php, ...)
|
|-- lib/                         # Core library
|   |-- main.php                 # Application bootstrap loader
|   |-- common.php               # Constants and shared functions
|   |-- controller/              # Request controllers (20 files)
|   |-- core/                    # Core classes (Bootstrap, Dispatcher, DbFactory, ...) (102 files)
|   |-- dao/                     # Data Access Objects (19 files)
|   |-- dto/                     # Data transfer objects
|   |-- handler/                 # Request and action handlers (13 files + admin commands)
|   |-- model/                   # Data models (9 files)
|   |-- service/                 # Business logic layer (24 files)
|   |-- utility/                 # Helper functions (224 files)
|   |-- validator/               # Input validation (5 files)
|   +-- vendor/                  # Composer dependencies
|
|-- public/                      # Public assets and generated files
|   |-- themes/                  # Theme templates (blog = default theme)
|   |-- files/                   # User uploads (pictures, audio, video, docs)
|   |-- cache/                   # Runtime cache
|   +-- log/                     # Log files
|
|-- docs/                        # Developer and user documentation
|   |-- dev-docs/                # Developer guides and API reference
|   +-- user-docs/               # End-user documentation
|
|-- tests/                       # PHPUnit test suite
|   |-- unit/                    # Unit tests
|   |-- core/                    # Core class tests
|   |-- service/                 # Service tests
|   |-- integration/             # Integration tests
|   |-- api/                     # API tests
|   |-- smoke/                   # Smoke tests
|   +-- fixtures/                # Test fixtures
|
+-- composer.json                # Dependencies and scripts
+-- phpunit.xml                  # PHPUnit configuration
+-- phpstan.neon                 # PHPStan static analysis configuration
+-- phpcs.xml                    # PHP_CodeSniffer configuration
+-- psalm.xml                    # Psalm static analysis configuration
```

> **SECURITY:** The Defuse encryption key is written to `../storage/keys/` - a sibling of the application root, outside the web root (see `generate_defuse_key()` in `install/include/setup.php`).

For detailed architecture and component documentation, see [DEVELOPER_GUIDE.md](docs/dev-docs/DEVELOPER_GUIDE.md).

## Development

Scriptlog adheres to **PSR-12** coding standards and uses **Conventional Commits**.

### Architecture

Scriptlog uses a **multi-layer architecture** designed for maintainability and scalability:

```
Request -> Front Controller -> Bootstrap -> Dispatcher -> Controller -> Service -> DAO -> Database
```

| Step | Component | Location |
|------|-----------|----------|
| 1 | **Front Controller** | `index.php` |
| 2 | **Bootstrap** | `lib/core/Bootstrap.php` |
| 3 | **Dispatcher** | `lib/core/Dispatcher.php` |
| 4 | **Controller** | `lib/controller/*` |
| 5 | **Service** | `lib/service/*` |
| 6 | **DAO** | `lib/dao/*` |
| 7 | **View** | `lib/core/View.php` |

### Adding New Features

When adding features, follow the layered implementation pattern:
1. **Database Table:** Add to `install/include/dbtable.php`
2. **DAO:** Create in `lib/dao/` (Database interactions)
3. **Service:** Create in `lib/service/` (Business logic)
4. **Controller:** Create in `lib/controller/` (Request handling)
5. **Routes:** Add to `lib/core/Bootstrap.php`

> **WARNING:** Never bypass the DAO layer when accessing the database. Always use prepared statements to prevent SQL injection.

### Key Commands

Run the following from the project root:
- **Run Tests:** `lib/vendor/bin/phpunit`
- **Static Analysis:** `lib/vendor/bin/phpstan analyse` (see [TESTING_GUIDE.md](docs/dev-docs/TESTING_GUIDE.md))

## Security Features

- **Authentication:** Custom secure session handler (`SessionMaker`) with remember-me tokens and session fingerprinting.
- **CSRF:** Protected via `csrf_defender` and form security utilities.
- **XSS:** Multi-layered prevention using `Anti-XSS` (voku) and `HTMLPurifier`.
- **Encryption:** Sensitive data encrypted using `defuse/php-encryption` with auto-generated keys.
- **Password Hashing:** Uses PHP's built-in `password_hash()` with bcrypt.
- **Access Control:** Role-based user levels with granular permissions.

### User Levels

| Level | Permissions |
|-------|-------------|
| **administrator** | USERS, IMPORT, PRIVACY, PLUGINS, THEMES, CONFIGURATION, PAGES, NAVIGATION, TOPICS, COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **manager** | PLUGINS, THEMES, CONFIGURATION, PAGES, NAVIGATION, TOPICS, COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **editor** | TOPICS, POSTS, DASHBOARD |
| **author** | COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **contributor** | POSTS, DASHBOARD |
| **subscriber** | DASHBOARD only |

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
