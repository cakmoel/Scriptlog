# Developer Guide - Scriptlog

**Version:** 1.1.0 | **Last Updated:** April 2026

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Architecture Overview](#2-architecture-overview)
3. [Directory Structure](#3-directory-structure)
4. [Core Components](#4-core-components)
5. [Database Schema](#5-database-schema)
6. [Creating New Features](#6-creating-new-features)
7. [Working with DAOs](#7-working-with-daos)
8. [Working with Services](#8-working-with-services)
9. [Working with Controllers](#9-working-with-controllers)
10. [Working with Models](#10-working-with-models)
11. [Utility Functions](#11-utility-functions)
12. [Theming](#12-theming)
13. [Plugins](#13-plugins)
14. [API Reference](#14-api-reference)
15. [Testing](#15-testing)
16. [Troubleshooting](#16-troubleshooting)
17. [Asset Management](#17-asset-management)
18. [GDPR Compliance](#18-gdpr-compliance)
19. [Internationalization (i18n)](#19-internationalization-i18n)
20. [Comment-Reply System](#20-comment-reply-system)
21. [Content Import System](#21-content-import-system)
22. [Content Export System](#22-content-export-system)
23. [UI Asset Management](#23-ui-asset-management)
24. [Dynamic SMTP System](#24-dynamic-smtp-system)
 25. [Search Functionality](#25-search-functionality)
 26. [Premium UI Standards](#26-premium-ui-standards)
  27. [Password-Protected Posts](#27-password-protected-posts)
  28. [Summernote AJAX Image Upload](#28-summernote-ajax-image-upload)

> **NOTE:** For comprehensive testing documentation including PHPStan setup and CI/CD integration, see [TESTING_GUIDE.md](TESTING_GUIDE.md).

---

## 1. Getting Started

### Prerequisites

| Requirement | Version | Purpose |
|-------------|---------|---------|
| **PHP** | 7.4+ | Server-side runtime |
| **MySQL/MariaDB** | 5.7+ | Database server |
| **Apache/Nginx** | Latest | Web server |
| **Composer** | Latest | Dependency management |

> **NOTE:** PHP extensions required: `pdo`, `pdo_mysql`, `json`, `mbstring`, `curl`

### Installation

```
+---------------------------------------------------------------+
|  INSTALLATION STEPS                                           |
+---------------------------------------------------------------+
|  1. Clone the repository                                      |
|  2. Navigate to project directory                             |
|  3. Access /install/ in browser                               |
|  4. Run install/index.php (system requirements)               |
|  5. Run install/setup-db.php (create tables)                  |
|  6. Run install/finish.php (complete setup)                   |
|  7. Configuration saved to config.php and .env               |
+---------------------------------------------------------------+
```

**Step-by-Step Installation**

### Option 1: Clone from GitHub

```bash
# Clone repository
git clone https://github.com/ScriptLog/scriptlog.git
cd scriptlog

# Install dependencies
composer install

# Set permissions
chmod -R 755 public/
chmod -R 777 public/cache/ public/log/
```

### Option 2: Install via Composer from Packagist

```bash
# Create project directory
mkdir my-scriptlog
cd my-scriptlog

# Initialize composer (create composer.json first)
composer init --name="my/scriptlog" --type=project --no-interaction

# Require the package with dev-develop branch
composer require cakmoel/scriptlog:dev-develop --prefer-stable

# Or use minimum-stability dev in composer.json
# "minimum-stability": "dev",
# "prefer-stable": true
# Then: composer require cakmoel/scriptlog

# The package will be installed in vendor/ directory
# Entry point is in src/ directory
```

### Running the Application

```bash
# From project root (recommended)
cd /path/to/your-project
php -S localhost:8080 -t src

# Or from within src directory
cd /path/to/your-project/src
php -S localhost:8080
```

Then access the application at: **http://localhost:8080**

> **NOTE:** The `-t src` flag tells PHP's built-in server that the `src` directory is the document root. Without this flag, the server cannot locate `index.php` and will return a "Failed to open stream" error.

> **TIP:** On Linux/Mac, ensure the web server user has write permissions to `public/cache/` and `public/log/`

### Post-Installation

| Environment | URL |
|-------------|-----|
| **Public Site** | `http://localhost:8080/` |
| **Admin Panel** | `http://localhost:8080/admin/` |
| **API Endpoint** | `http://localhost:8080/api/v1/` |
| **Installation Wizard** | `http://localhost:8080/install/` |

> **NOTE:** After installation, access `/install/` in your browser to set up the database and complete the setup.

---

## 2.1 Configuration System

### Overview

ScriptLog supports both `.env` and `config.php` files for configuration. During first-time installation, both files are automatically generated and kept in sync.

### Configuration Files

#### config.php

The main configuration file uses `$_ENV` pattern with fallback values:

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
        'url'   => $_ENV['APP_URL'] ?? 'http://example.com',
        'email' => $_ENV['APP_EMAIL'] ?? '',
        'key'   => $_ENV['APP_KEY'] ?? '',
        'defuse_key' => 'lib/utility/.lts/lts.txt'
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
        'distrib_name'    => $_ENV['DISTRIB_NAME'] ?? ''
    ],
];
```

#### .env File

Auto-generated environment file:

```bash
# --- DATABASE CONFIGURATION ---
DB_HOST=localhost
DB_USER=blogwareuser
DB_PASS=yourpassword
DB_NAME=blogwaredb
DB_PORT=3306
DB_PREFIX=

# --- APPLICATION CONFIGURATION ---
APP_URL=https://example.com
APP_EMAIL=admin@example.com
APP_KEY=XXXXXX-XXXXXX-XXXXXX-XXXXXX

# --- MAIL / SMTP CONFIGURATION ---
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME=Blogware

# --- SYSTEM ---
SYSTEM_OS=Linux
DISTRIB_NAME="Linux Mint"
```

### Automatic Defuse Key Generation

During first-time installation, the system automatically:
- Generates a Defuse encryption key using `Defuse\Crypto\Key::createNewRandomKey()`
- Saves the key to outside of web root
- Stores the key path in `config.php` under `app.defuse_key`
- This key is used for authentication cookie encryption

### Installation Fixes

During development, the following bugs were discovered and fixed:

#### 1. write_config_file() Argument Order Bug

**Problem**: The `write_config_file()` function in `install/include/setup.php` has the signature:
```php
function write_config_file($protocol, $server_name, $dbhost, $dbpassword, $dbuser, $dbname, $dbport, ...)
```

But `install/index.php` was calling it with `$dbuser` and `$dbpass` in the wrong order, causing "Access denied" errors when creating config.php.

**Fix**: In `install/index.php` line 168, corrected the argument order:
```php
// BEFORE (WRONG):
write_config_file($protocol, $server_host, $dbhost, $dbuser, $dbpass, $dbname, ...)

// AFTER (CORRECT):
write_config_file($protocol, $server_host, $dbhost, $dbpass, $dbuser, $dbname, ...)
```

#### 2. popper.min.js Path Bug

**Problem**: The install layout was loading popper.js from a non-existent path `assets/vendor/bootstrap/js/vendor/popper.min.js`.

**Fix**: In `install/install-layout.php`, corrected the path to `assets/vendor/bootstrap/js/popper.min.js`.

#### 3. Database Tables Created

The installation now creates 21 tables:
- Core: tbl_users, tbl_user_token, tbl_login_attempt, tbl_posts, tbl_topics, tbl_post_topic, tbl_comments
- Media: tbl_media, tbl_mediameta, tbl_media_download
- System: tbl_menu, tbl_plugin, tbl_settings, tbl_themes
- GDPR: tbl_consents, tbl_data_requests, tbl_privacy_logs, tbl_privacy_policies
- i18n: tbl_languages, tbl_translations
- Downloads: tbl_download_log

#### 4. Database Column Fixes

**Problem**: The `PostModel` class references a `post_keyword` column that didn't exist in the database, causing errors when accessing post data.

**Fix**: Added `post_keyword` column to `tbl_posts` in `install/include/dbtable.php`:
```sql
ALTER TABLE tbl_posts ADD COLUMN post_keyword VARCHAR(255) DEFAULT NULL AFTER post_tags;
```

#### 5. Db Class KnownTables Array

**Problem**: The `Db` class (`lib/core/Db.php`) had an incomplete `knownTables` array, missing several tables that the application uses. This caused issues with prefix handling.

**Fix**: Added all 21 tables to the `knownTables` array in `lib/core/Db.php`:
```php
private $knownTables = [
    'users', 'user_token', 'login_attempt', 'posts', 'topics', 
    'post_topic', 'comments', 'media', 'mediameta', 'media_download',
    'menu', 'plugin', 'settings', 'themes', 'consents', 
    'data_requests', 'privacy_logs', 'privacy_policies', 
    'languages', 'translations', 'download_log'
];
```

### Post-Installation Fixes

After initial installation, several issues were discovered and fixed:

#### 6. Table Prefix Compatibility

**Problem**: The application uses table prefixes (e.g., `urmpnj_posts`) but utility functions using Medoo were creating new database connections without applying the prefix.

**Fixes**:

1. **medooin.php**: Modified to use the Registry connection (`Registry::get('dbc')`) instead of creating a new Medoo connection, ensuring table prefix is applied.

2. **db-mysqli.php**: Updated to work with both PDO and mysqli connections - checks connection type and handles accordingly.

#### 7. Null Safety and Compatibility Fixes

The following utility functions were fixed for null safety and PDO/mysqli compatibility:

1. **membership.php** (`lib/utility/membership.php`):
   - Added null checks for `$user['user_fullname']` and `$user['user_login']`
   - Fixed array access patterns

2. **app-info.php** (`lib/utility/app-info.php`):
   - Fixed array/object compatibility issues
   - Added type checking for different return formats

3. **theme-navigation.php** (`lib/utility/theme-navigation.php`):
   - Added PDO/mysqli compatibility handling
   - Fixed result fetching for both connection types

4. **login-attempt.php** (`lib/utility/login-attempt.php`):
   - Added PDO/mysqli compatibility handling
   - Fixed result fetching patterns

#### 8. Hello World Plugin Installation

**Problem**: The sample Hello World plugin existed in `admin/plugins/hello-world/` but was not being added to the database during installation.

**Fix**: Added the plugin to the installation process:

1. **dbtable.php**: Added `savePlugin` SQL query:
```php
$savePlugin = "INSERT INTO {$prefix}tbl_plugin (plugin_name, plugin_link, plugin_directory, plugin_desc, plugin_status, plugin_level, plugin_sort) VALUES (?, ?, ?, ?, ?, ?, ?)";
```

2. **setup.php**: Added code to insert Hello World plugin during installation:
```php
$plugin_name = "Hello World";
$plugin_link = "#";
$plugin_directory = "hello-world";
$plugin_desc = "A simple Hello World plugin to demonstrate the plugin system";
$plugin_status = "N"; // disabled by default
$plugin_level = "administrator";
$plugin_sort = 1;
```

The plugin is inserted as disabled (`plugin_status = 'N'`) by default, allowing users to enable it from the admin panel after installation.

### Key Files

| File | Location | Purpose |
|------|----------|---------|
| `config.php` | Root | Main configuration with `$_ENV` fallbacks |
| `.env` | Root | Environment variables (auto-generated) |
| `defuse_key` | `lib/utility/.lts/lts.txt` | Encryption key for authentication |

---

## 2. Architecture Overview

ScriptLog uses a **multi-layer architecture** designed for maintainability and scalability:

```
+---------------------------------------------------------------+
|                     REQUEST FLOW                              |
+---------------------------------------------------------------+
|                                                               |
|   Request                                                     |
|     |                                                         |
|     v                                                         |
|   +---------------------+                                     |
|   | Front Controller    |  (index.php / admin/index.php)      |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | Bootstrap           |  (lib/core/Bootstrap.php)           |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | Dispatcher          |  (lib/core/Dispatcher.php)          |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | Controller          |  (lib/controller/*)                 |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | Service             |  (lib/service/*)                    |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | DAO                 |  (lib/dao/*)                        | 
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | Database            |  (MySQL/MariaDB)                    |
|   +---------------------+                                     |
|                                                               |
+---------------------------------------------------------------+
```

### Request Flow Breakdown

| Step | Component | File | Description |
|------|-----------|------|-------------|
| 1 | **Front Controller** | `index.php` | Entry point for requests |
| 2 | **Bootstrap** | `lib/core/Bootstrap.php` | Initializes app and services |
| 3 | **Dispatcher** | `lib/core/Dispatcher.php` | Routes request to controller |
| 4 | **Controller** | `lib/controller/*` | Handles HTTP logic |
| 5 | **Service** | `lib/service/*` | Business logic layer |
| 6 | **DAO** | `lib/dao/*` | Data access layer |
| 7 | **View** | `lib/core/View.php` | Renders output |

> **WARNING:** Never bypass the DAO layer when accessing the database. Always use prepared statements to prevent SQL injection.

### 404 Handling

All 404 handling is done in the Dispatcher, NOT in theme templates. This prevents "headers already sent" errors:

- **Dispatcher** (`lib/core/Dispatcher.php`): Contains `validateContentExists()` method that checks if content exists in database before rendering
- **Validation happens BEFORE header output**: Ensures proper 404 status code is set
- **Route parameter names**: Use correct named parameters from route patterns (`id` for posts, `page` for pages, `category` for categories)
- **Custom 404 template**: Uses theme's `404.php` template
- **HandleRequest** (`lib/core/HandleRequest.php`): Handles query string URLs when permalinks are disabled, renders custom 404 template for invalid paths

```php
// Example: validateContentExists in Dispatcher
private function validateContentExists($routeKey, $requestPath)
{
    switch ($routeKey) {
        case 'single':
            $postId = isset($requestPath->id) ? $requestPath->id : null;
            $postSlug = isset($requestPath->post) ? $requestPath->post : null;
            
            if (empty($postId) || empty($postSlug)) {
                return false;
            }
            
            $post = class_exists('FrontHelper') ? FrontHelper::grabPreparedFrontPostById($postId) : null;
            
            if (empty($post) || !is_array($post)) {
                return false;
            }
            
            // Validate slug matches - redirect to 404 if slug is incorrect
            $dbSlug = isset($post['post_slug']) ? $post['post_slug'] : '';
            return ($dbSlug === $postSlug);
        // ... other cases
    }
}
```

**Important**: A `.htaccess` file is required for Apache to route all requests to `index.php`. This ensures the PHP-based routing works regardless of permalink settings.

```apache
# .htaccess - Required for Apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</IfModule>
```

Do NOT add http_response_code() in theme templates - let the Dispatcher handle 404s.

### Canonical URL Validation

The Dispatcher validates that the URL slug matches the database slug for posts and pages. This ensures canonical URL enforcement and prevents duplicate content:

- `/post/2/cicero` → post exists with slug "cicero" → renders post
- `/post/2/ciceros` → post ID 2 exists but slug is "cicero" (not "ciceros") → returns 404
- `/page/about-us` → page exists with slug "about-us" → renders page
- `/page/about-us-extra` → page slug doesn't match → returns 404

This prevents SEO issues from duplicate content when users access pages with incorrect slugs.

### Tag URLs

Tags are stored as comma-separated values in `tbl_posts.post_tags` column (e.g., "cicero,lorem ipsum,MariaDB"). The tag system works as follows:

| Aspect | Details |
|--------|---------|
| **Route Pattern** | `/tag/(?'tag'[\w\- ]+)` - supports spaces and hyphens |
| **URL Encoding** | Spaces encoded as `%20` (e.g., `/tag/lorem%20ipsum`) |
| **URL Decoding** | `RequestPath` class decodes automatically for SEO-friendly; `HandleRequest::isQueryStringRequested()` decodes for query string |
| **Validation** | Dispatcher uses `FrontHelper::simpleSearchingTag()` to verify posts exist |
| **Search** | Uses LIKE query (`%tag%`) to match tags in comma-separated list |

**SEO-Friendly URL (Enabled)**:
- Pattern: `/tag/your-tag` (supports spaces via URL encoding)
- Parameters extracted via `request_path()` object (tag property)
- Use `is_permalink_enabled()` to check if SEO-friendly URLs are enabled

**Query String URL (Disabled)**:
- Pattern: `?tag=your-tag` (spaces encoded as %20)
- Parameters extracted via `HandleRequest::isQueryStringRequested()`['value']
- Use `urldecode()` in HandleRequest to handle URL-encoded values

**Examples:**
- `/tag/cicero` - shows posts with tag "cicero" (SEO-friendly)
- `/tag/lorem%20ipsum` - shows posts with tag "lorem ipsum" (SEO-friendly)
- `?tag=lorem` - shows posts with tag "lorem" (query string)
- `?tag=lorem%20ipsum` - shows posts with tag "lorem ipsum" (query string)

**Key Files:**
- `lib/core/Bootstrap.php` - Route pattern definition
- `lib/core/Dispatcher.php` - Tag validation in `validateContentExists()`
- `lib/core/RequestPath.php` - URL decoding for `%20` spaces
- `lib/core/HandleRequest.php` - `isQueryStringRequested()` for query string URLs
- `lib/core/FrontHelper.php` - `simpleSearchingTag()` method
- `lib/utility/permalinks.php` - `is_permalink_enabled()` function
- `lib/model/TagModel.php` - `getPostsPublishedByTag()` method
- `public/themes/blog/tag.php` - Tag archive template

### Archive URLs

Archive functionality allows users to browse posts by month/year:

| Aspect | Details |
|--------|---------|
| **Route Patterns** | `/archive/[0-9]{2}/[0-9]{4}` for monthly archives, `/archives` for index |
| **Archive Index** | Groups archives by year, shows month name and post count |
| **Pagination** | Uses `post_per_archive` setting |
| **Validation** | Dispatcher checks if posts exist in archive before rendering |

**SEO-Friendly URL (Enabled)**:
- Pattern: `/archive/03/2025` (month/year format)
- Parameters extracted via `request_path()` object (param1 = month, param2 = year)
- Use `is_permalink_enabled()` to check if SEO-friendly URLs are enabled

**Query String URL (Disabled)**:
- Pattern: `?a=032025` (6-digit format: year + month)
- Parameters extracted via `HandleRequest::isQueryStringRequested()`['value']
- Use `preg_split("//", ...)` to split the string and extract year (indices 0-3) and month (indices 4-5)

**Examples:**
- `/archives` - Shows all archive dates grouped by year
- `/archive/03/2025` - Shows posts from March 2025 (SEO-friendly)
- `?a=032025` - Shows posts from March 2025 (query string)

**Key Files:**
- `lib/core/Bootstrap.php` - Route patterns for `archive` and `archives`
- `lib/core/Dispatcher.php` - Archive validation in `validateContentExists()`
- `lib/model/ArchivesModel.php` - `getPostsByArchive()`, `getArchiveIndex()`
- `lib/model/FrontContentModel.php` - `frontPostsByArchive()`, `frontArchiveIndex()`
- `lib/utility/permalinks.php` - `listen_query_string()` for archive URL generation
- `public/themes/blog/archive.php` - Archive month template
- `public/themes/blog/archives.php` - Archive index template

---

## 3. Directory Structure

```
ScriptLog/public_html/
|
|-- index.php                    # Public front controller
|-- config.php                   # Application configuration
|
|-- admin/                      # Admin panel
|   |-- index.php               # Admin entry point
|   |-- login.php               # Login page
|   |-- posts.php               # Post management
|   |-- pages.php               # Page management
|   |-- topics.php              # Category management
|   |-- comments.php            # Comment management
|   |-- reply.php               # Reply management
|   |-- users.php               # User management
|   |-- menu.php                # Menu management
|   |-- templates.php           # Theme management
|   |-- plugins.php             # Plugin management
|   |-- medialib.php            # Media library
|   +-- ui/                     # Admin UI components
|       +-- comments/           # Comment UI templates
|           |-- all-comments.php
|           |-- edit-comment.php
|           |-- reply.php
|           +-- reply-list.php
|
|-- api/                        # RESTful API
|   +-- index.php               # API entry point
|
|-- lib/                       # Core library
|   |-- main.php               # Application bootstrap
|   |-- common.php             # Constants and functions
|   |-- options.php            # PHP configuration
|   |-- Autoloader.php         # Class autoloader
|   |-- utility-loader.php     # Utility functions loader
|   |
|   +-- core/                  # Core classes (80+ files)
|       |-- Bootstrap.php      # Application initialization
|       |-- Dispatcher.php     # URL routing
|       |-- DbFactory.php      # PDO database connection
|       |-- Authentication.php # User authentication
|       |-- SessionMaker.php   # Custom session handler
|       |-- View.php           # View rendering
|       |-- ApiResponse.php    # API response handler
|       |-- ApiAuth.php        # API authentication
|       |-- ApiRouter.php      # API routing
|       +-- ...
|
|   +-- dao/                  # Data Access Objects
|       |-- PostDao.php       # Posts CRUD
|       |-- UserDao.php       # Users CRUD
|       |-- CommentDao.php    # Comments CRUD
|       |-- TopicDao.php      # Topics CRUD
|       |-- MediaDao.php      # Media CRUD
|       |-- PageDao.php       # Pages CRUD
|       |-- MenuDao.php       # Menus CRUD
|       |-- PluginDao.php     # Plugins CRUD
|       |-- ThemeDao.php      # Themes CRUD
|       +-- ConfigurationDao.php
|
|   +-- service/               # Business logic layer
|       |-- PostService.php
|       |-- UserService.php
|       |-- CommentService.php
|       |-- TopicService.php
|       |-- MediaService.php
|       |-- PageService.php
|       |-- MenuService.php
|       |-- PluginService.php
|       |-- ThemeService.php
|       |-- ConfigurationService.php
|       +-- ReplyService.php
|
|   +-- controller/            # Request controllers
|       |-- PostController.php
|       |-- UserController.php
|       |-- CommentController.php
|       |-- TopicController.php
|       |-- MediaController.php
|       |-- PageController.php
|       |-- MenuController.php
|       |-- PluginController.php
|       |-- ThemeController.php
|       |-- ConfigurationController.php
|       |-- ReplyController.php
|       |
|       +-- api/              # API Controllers
|           |-- PostsApiController.php
|           |-- CategoriesApiController.php
|           |-- CommentsApiController.php
|           +-- ArchivesApiController.php
|
|   +-- model/                # Data models
|       |-- PostModel.php
|       |-- FrontContentModel.php
|       |-- TopicModel.php
|       |-- TagModel.php
|       |-- PageModel.php
|       |-- CommentModel.php
|       |-- GalleryModel.php
|       |-- ArchivesModel.php
|       +-- DownloadModel.php
|
|   +-- utility/              # Utility functions (100+ files)
|       |-- invoke-config.php
|       |-- form-security.php
|       |-- csrf-defender.php
|       |-- remove-xss.php
|       |-- email-validation.php
|       +-- ...
|
|   +-- vendor/              # Composer dependencies
|
|-- public/                  # Public web root
|   +-- themes/              # Theme templates
|       +-- blog/            # Default theme
|   +-- files/               # Uploaded files
|       |-- pictures/
|       |-- audio/
|       |-- video/
|       +-- docs/
|   +-- cache/               # Cache directory
|   +-- log/                 # Log directory
|
|-- docs/                    # Documentation
|   |-- DEVELOPER_GUIDE.md
|   |-- TESTING_GUIDE.md
|   |-- PLUGIN_DEVELOPER_GUIDE.md
|   |-- API_DOCUMENTATION.md
|   |-- API_OPENAPI.yaml
|   +-- API_OPENAPI.json
|
+-- install/                  # Installation wizard
    |-- index.php
    |-- setup-db.php
    |-- finish.php
    +-- include/
        |-- dbtable.php
        |-- setup.php
        +-- settings.php
```

> **TIP:** Use `APP_ROOT`, `APP_ADMIN`, `APP_PUBLIC`, and other constants defined in `lib/common.php` for path handling.

---

## 4. Core Components

### Bootstrap (`lib/core/Bootstrap.php`)

Initializes the application and sets up the service container.

```php
// Initialize the application
$vars = Bootstrap::initialize(APP_ROOT);

// Returns array with:
// - Database credentials
// - Services (authenticator, sessionMaker, userDao, etc.)
```

### Dispatcher (`lib/core/Dispatcher.php`)

Handles URL routing and dispatches requests to appropriate controllers. Also validates content exists before rendering to handle 404s properly.

```php
// Route patterns defined in Bootstrap
$rules = [
    'home'     => "/",
    'category' => "/category/(?'category'[\w\-]+)",
    'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
    'archives' => "/archives",
    'blog'     => "/blog([^/]*)",
    'page'     => "/page/(?'page'[^/]+)",
    'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
    'search'   => "(?'search'[\w\-]+)",
    'tag'      => "/tag/(?'tag'[\w\- ]+)"
];
```

#### Content Validation

The Dispatcher validates content exists in the database before rendering templates to ensure proper 404 handling:

- Uses named parameters from route patterns (`id`, `page`, `category`)
- Checks database via FrontHelper methods
- Calls `errorNotFound()` before any output if content not found

### DbFactory (`lib/core/DbFactory.php`)

Creates PDO database connections.

```php
$dbc = DbFactory::connect([
    'mysql:host=localhost;port=3306;dbname=ScriptLogdb',
    'username',
    'password'
]);
```

### Authentication (`lib/core/Authentication.php`)

Handles user authentication, login, logout, and session management.

#### Key Features

- **Login**: Accepts email or username, validates credentials, creates session
- **Remember Me**: Uses three cookies (scriptlog_auth, scriptlog_validator, scriptlog_selector) with token-based authentication
- **Session Fingerprinting**: Stores IP address and HMAC-hashed user agent for session validation
- **Cookie Encryption**: Uses Defuse/php-encryption for secure cookie storage
- **Access Control**: `userAccessControl()` method implements role-based permissions

#### Session Data

When a user logs in, these session variables are set:
- `scriptlog_session_id` - User ID
- `scriptlog_session_email` - User email
- `scriptlog_session_level` - User level (administrator, manager, editor, author, contributor, subscriber)
- `scriptlog_session_login` - Username
- `scriptlog_session_fullname` - Full name
- `scriptlog_session_agent` - User agent fingerprint
- `scriptlog_session_ip` - Client IP address
- `scriptlog_fingerprint` - HMAC-based session fingerprint
- `scriptlog_last_active` - Last activity timestamp

#### User Levels and Access Control

| Level | Permissions |
|-------|-------------|
| **administrator** | Full access - PRIVACY, USERS, IMPORT, PLUGINS, THEMES, CONFIGURATION, PAGES, NAVIGATION, TOPICS, COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **manager** | PLUGINS, THEMES, CONFIGURATION, PAGES, NAVIGATION, TOPICS, COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **editor** | TOPICS, COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **author** | COMMENTS, MEDIALIB, REPLY, POSTS, DASHBOARD |
| **contributor** | POSTS, DASHBOARD |
| **subscriber** | DASHBOARD only |

#### Access Control Implementation

```php
// In admin pages, check authorization before processing
if (false === $authenticator->userAccessControl(ActionConst::PRIVACY)) {
    direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
}
```

### SessionMaker (`lib/core/SessionMaker.php`)

Custom session handler with secure cookie management.

---

## 5. Database Schema

### Table: tbl_users

```sql
CREATE TABLE tbl_users (
    ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    user_login VARCHAR(60) NOT NULL UNIQUE,
    user_email VARCHAR(100) NOT NULL UNIQUE,
    user_pass VARCHAR(255) NOT NULL,
    user_level VARCHAR(20) NOT NULL,
    user_fullname VARCHAR(120) DEFAULT NULL,
    user_url VARCHAR(100) DEFAULT NULL,
    user_registered datetime NOT NULL,
    user_activation_key VARCHAR(255),
    user_session VARCHAR(255) NOT NULL,
    user_banned TINYINT DEFAULT '0',
    user_signin_count INT DEFAULT '0',
    user_locked_until DATETIME NULL,
    PRIMARY KEY (ID)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: tbl_posts

```sql
CREATE TABLE tbl_posts (
    ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    media_id BIGINT(20) UNSIGNED DEFAULT '0',
    post_author BIGINT(20) UNSIGNED NOT NULL,
    post_date datetime NOT NULL,
    post_modified datetime DEFAULT NULL,
    post_title tinytext NOT NULL,
    post_slug text NOT NULL,
    post_content longtext NOT NULL,
    post_summary mediumtext DEFAULT NULL,
    post_status VARCHAR(20) DEFAULT 'publish',
    post_visibility VARCHAR(20) DEFAULT 'public',
    post_password VARCHAR(255) DEFAULT NULL,
    post_tags text DEFAULT NULL,
    post_type VARCHAR(120) DEFAULT 'blog',
    comment_status VARCHAR(20) DEFAULT 'open',
    PRIMARY KEY (ID),
    KEY author_id(post_author),
    FULLTEXT KEY (post_tags, post_title, post_content)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: tbl_topics (Categories)

```sql
CREATE TABLE tbl_topics (
    ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    topic_title VARCHAR(255) NOT NULL,
    topic_slug VARCHAR(255) NOT NULL,
    topic_status ENUM('Y','N') DEFAULT 'Y',
    PRIMARY KEY (ID)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: tbl_comments

```sql
CREATE TABLE tbl_comments (
    ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    comment_post_id BIGINT(20) unsigned NOT NULL,
    comment_parent_id BIGINT(20) DEFAULT '0',
    comment_author_name VARCHAR(60) NOT NULL,
    comment_author_ip VARCHAR(100) NOT NULL,
    comment_author_email VARCHAR(100) DEFAULT NULL,
    comment_content text NOT NULL,
    comment_status VARCHAR(20) DEFAULT 'pending',
    comment_date datetime NOT NULL,
    PRIMARY KEY (ID)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: tbl_media

```sql
CREATE TABLE tbl_media (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    media_filename VARCHAR(200) DEFAULT NULL,
    media_caption VARCHAR(200) DEFAULT NULL,
    media_type VARCHAR(90) NOT NULL,
    media_target VARCHAR(20) DEFAULT 'blog',
    media_user VARCHAR(20) NOT NULL,
    media_access VARCHAR(10) DEFAULT 'public',
    media_status INT DEFAULT '0',
    PRIMARY KEY (ID)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: tbl_settings

```sql
CREATE TABLE tbl_settings (
    ID INT(11) unsigned NOT NULL AUTO_INCREMENT,
    setting_name VARCHAR(255) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    PRIMARY KEY (ID),
    KEY setting_name(setting_name(191))
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **NOTE:** For complete table definitions including `tbl_post_topic`, `tbl_user_token`, `tbl_login_attempt`, `tbl_mediameta`, `tbl_media_download`, `tbl_menu`, `tbl_plugin`, and `tbl_themes`, see `install/include/dbtable.php`.

---

## 6. Creating New Features

### Adding a New Database Table

| Step | Action | Location |
|------|--------|----------|
| 1 | Add table definition | `install/include/dbtable.php` |
| 2 | Create DAO class | `lib/dao/` |
| 3 | Create service class | `lib/service/` |
| 4 | Create controller | `lib/controller/` |
| 5 | Add routes | `lib/core/Bootstrap.php` |

### Example: Creating a Newsletter Feature

#### Step 1: Database Table

```php
// Add to install/include/dbtable.php
$tblNewsletter = "CREATE TABLE IF NOT EXISTS tbl_newsletter (
    ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    subscriber_email VARCHAR(100) NOT NULL UNIQUE,
    subscriber_token VARCHAR(255) DEFAULT NULL,
    subscribed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribe_at datetime DEFAULT NULL,
    status ENUM('active','unsubscribed') DEFAULT 'active',
    PRIMARY KEY (ID)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4";
```

#### Step 2: DAO

```php
// lib/dao/NewsletterDao.php
<?php
class NewsletterDao
{
    private $db;

    public function __construct()
    {
        $this->db = DbFactory::connect([...]);
    }

    public function subscribe($email)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_newsletter (subscriber_email) VALUES (?)"
        );
        $stmt->execute([$email]);
        return $this->db->lastInsertId();
    }

    public function unsubscribe($email)
    {
        $stmt = $this->db->prepare(
            "UPDATE tbl_newsletter SET status = 'unsubscribed', 
             unsubscribe_at = NOW() WHERE subscriber_email = ?"
        );
        return $stmt->execute([$email]);
    }

    public function getActiveSubscribers()
    {
        $stmt = $this->db->query(
            "SELECT * FROM tbl_newsletter WHERE status = 'active'"
        );
        return $stmt->fetchAll();
    }
}
```

#### Step 3: Service

```php
// lib/service/NewsletterService.php
<?php
class NewsletterService
{
    private $newsletterDao;

    public function __construct(NewsletterDao $newsletterDao)
    {
        $this->newsletterDao = $newsletterDao;
    }

    public function subscribe($email)
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email address");
        }

        return $this->newsletterDao->subscribe($email);
    }

    public function unsubscribe($email)
    {
        return $this->newsletterDao->unsubscribe($email);
    }
}
```

#### Step 4: Controller

```php
// lib/controller/NewsletterController.php
<?php
class NewsletterController
{
    private $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    public function subscribe()
    {
        $email = $_POST['email'] ?? '';

        try {
            $this->newsletterService->subscribe($email);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
```

> **TIP:** Always validate input in the service layer, not in DAOs. Keep DAOs focused on data operations only.

---

## 7. Working with DAOs

### DAO Pattern Guidelines

| Guideline | Description |
|-----------|-------------|
| **Single Responsibility** | Each DAO handles one database table |
| **Prepared Statements** | Use for all queries to prevent SQL injection |
| **Return Format** | Return associative arrays or objects |
| **Error Handling** | Handle exceptions gracefully |

### Example: PostDao

```php
class PostDao
{
    private $db;

    public function __construct()
    {
        $this->db = Registry::get('dbc');
    }

    public function insertPost($data)
    {
        $sql = "INSERT INTO tbl_posts 
                (post_author, post_title, post_slug, post_content, 
                 post_status, post_type, post_date) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['post_author'],
            $data['post_title'],
            $data['post_slug'],
            $data['post_content'],
            $data['post_status'],
            $data['post_type']
        ]);
    }

    public function updatePost($id, $data)
    {
        $sql = "UPDATE tbl_posts SET 
                post_title = ?, post_slug = ?, post_content = ?,
                post_modified = NOW() WHERE ID = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['post_title'],
            $data['post_slug'],
            $data['post_content'],
            $id
        ]);
    }

    public function deletePost($id)
    {
        $stmt = $this->db->prepare("DELETE FROM tbl_posts WHERE ID = ?");
        return $stmt->execute([$id]);
    }

    public function findPostById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.user_login, u.user_fullname 
             FROM tbl_posts p 
             LEFT JOIN tbl_users u ON p.post_author = u.ID 
             WHERE p.ID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findPublishedPosts($limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.user_login, u.user_fullname 
             FROM tbl_posts p 
             LEFT JOIN tbl_users u ON p.post_author = u.ID 
             WHERE p.post_status = 'publish' 
             ORDER BY p.post_date DESC 
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
```

---

## 8. Working with Services

### Service Layer Guidelines

| Principle | Description |
|-----------|-------------|
| **Business Logic** | Services contain business logic |
| **Validation** | Services validate input |
| **Data Access** | Services call DAOs |
| **Composition** | Services can call other services |

### Example: PostService

```php
class PostService
{
    private $postDao;
    private $mediaDao;
    private $topicDao;

    public function __construct(PostDao $postDao, MediaDao $mediaDao, TopicDao $topicDao)
    {
        $this->postDao = $postDao;
        $this->mediaDao = $mediaDao;
        $this->topicDao = $topicDao;
    }

    public function createPost($data)
    {
        // Validation
        if (empty($data['post_title'])) {
            throw new \Exception("Post title is required");
        }

        // Generate slug
        $data['post_slug'] = $this->generateSlug($data['post_title']);

        // Insert post
        $postId = $this->postDao->insertPost($data);

        // Handle topics
        if (!empty($data['post_topics'])) {
            $this->topicDao->setPostTopics($postId, $data['post_topics']);
        }

        return $postId;
    }

    public function publishPost($id)
    {
        return $this->postDao->updatePost($id, ['post_status' => 'publish']);
    }

    public function getPostWithMedia($id)
    {
        $post = $this->postDao->findPostById($id);
        
        if ($post && $post['media_id']) {
            $post['media'] = $this->mediaDao->findMediaById($post['media_id']);
        }

        return $post;
    }

    private function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title)));
        return $slug;
    }
}
```

---

## 9. Working with Controllers

### Controller Guidelines

| Guideline | Description |
|-----------|-------------|
| **HTTP Handling** | Controllers handle HTTP requests |
| **Service Calls** | Controllers call services |
| **Response Format** | Controllers return views or JSON |
| **Thin Design** | Keep controllers thin, move logic to services |

### Example: PostController

```php
class PostController
{
    private $postService;
    private $topicService;
    private $validator;

    public function __construct(
        PostService $postService,
        TopicService $topicService,
        FormValidator $validator
    ) {
        $this->postService = $postService;
        $this->topicService = $topicService;
        $this->validator = $validator;
    }

    public function create()
    {
        // Check authorization
        if (!current_user_can('create_posts')) {
            http_response_code(403);
            return ['error' => 'Unauthorized'];
        }

        // Validate input
        $this->validator->validate($_POST, [
            'post_title' => 'required|min:3',
            'post_content' => 'required|min:10'
        ]);

        if ($this->validator->hasErrors()) {
            return ['errors' => $this->validator->getErrors()];
        }

        try {
            $postId = $this->postService->createPost($_POST);
            return ['success' => true, 'post_id' => $postId];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function update($id)
    {
        if (!current_user_can('edit_post', $id)) {
            http_response_code(403);
            return ['error' => 'Unauthorized'];
        }

        try {
            $this->postService->updatePost($id, $_POST);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        if (!current_user_can('delete_post', $id)) {
            http_response_code(403);
            return ['error' => 'Unauthorized'];
        }

        try {
            $this->postService->deletePost($id);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
```

---

## 10. Working with Models

### Model Guidelines

| Principle | Description |
|-----------|-------------|
| **Data Entities** | Models represent data entities |
| **Transformation** | Models can contain data transformation logic |
| **View Preparation** | Models are used for view data preparation |

### Example: PostModel

```php
class PostModel
{
    public $ID;
    public $post_title;
    public $post_slug;
    public $post_content;
    public $post_summary;
    public $post_date;
    public $post_modified;
    public $post_status;
    public $post_type;
    public $post_tags;
    public $author_name;
    public $media_filename;

    public static function fromDbRow($row)
    {
        $model = new self();
        $model->ID = $row['ID'];
        $model->post_title = $row['post_title'];
        $model->post_slug = $row['post_slug'];
        $model->post_content = $row['post_content'];
        $model->post_summary = $row['post_summary'];
        $model->post_date = $row['post_date'];
        $model->post_modified = $row['post_modified'];
        $model->post_status = $row['post_status'];
        $model->post_type = $row['post_type'];
        $model->post_tags = $row['post_tags'];
        $model->author_name = $row['user_fullname'] ?? $row['user_login'];
        $model->media_filename = $row['media_filename'] ?? null;
        
        return $model;
    }

    public function getExcerpt($length = 150)
    {
        if ($this->post_summary) {
            return $this->post_summary;
        }
        
        return substr(strip_tags($this->post_content), 0, $length) . '...';
    }

    public function getFormattedDate($format = 'F j, Y')
    {
        return date($format, strtotime($this->post_date));
    }

    public function getTagsArray()
    {
        if (empty($this->post_tags)) {
            return [];
        }
        return array_map('trim', explode(',', $this->post_tags));
    }
}
```

---

## 11. Utility Functions

### Available Utility Functions

Utility functions are loaded via `lib/utility-loader.php` and include:

| Category | Functions |
|----------|-----------|
| **Security** | `csrf-defender.php`, `remove-xss.php`, `form-security.php` |
| **Validation** | `email-validation.php`, `url-validation.php` |
| **Plugins** | `plugin-helper.php`, `plugin-validator.php`, `invoke-plugin.php` |
| **Formatting** | `escape-html.php`, `limit-word.php` |
| **Media** | `invoke-frontimg.php`, `upload-video.php` |
| **Session** | `turn-on-session.php`, `regenerate-session.php` |

### Image Handling Functions

The blog includes comprehensive image display functions with WebP support and responsive images.

#### Image Storage Structure

```
public/files/pictures/
├── small/           # Thumbnail images (640x450)
│   └── small_*.jpg
├── medium/         # Medium images (730x486)
│   └── medium_*.jpg
├── large/          # Large images (1200x630)
│   └── large_*.jpg
├── *.webp          # WebP versions (shared with main folder)
└── *.jpg           # Original JPEG versions
```

#### Key Constants (lib/common.php)

```php
// Defined in lib/common.php:
define('APP_IMAGE', APP_PUBLIC . DS . 'files' . DS . 'pictures' . DS);
define('APP_IMAGE_LARGE', APP_IMAGE . 'large' . DS);
define('APP_IMAGE_MEDIUM', APP_IMAGE . 'medium' . DS);
define('APP_IMAGE_SMALL', APP_IMAGE . 'small' . DS);
```

#### Image Functions

| Function | Purpose | Location |
|----------|---------|----------|
| `invoke_webp_image()` | Returns WebP URL if available, else returns original | `lib/utility/invoke-webp-image.php` |
| `invoke_frontimg()` | Primary function for displaying featured images | `lib/utility/invoke-frontimg.php` |
| `invoke_responsive_image()` | Generates `<picture>` element with WebP support | `lib/utility/invoke-responsive-image.php` |
| `invoke_hero_image()` | Hero/LCP images with fetchpriority="high" | `lib/utility/invoke-responsive-image.php` |
| `invoke_gallery_image()` | Gallery images with lazy loading | `lib/utility/invoke-responsive-image.php` |

#### Function Signatures

```php
// Basic featured image
invoke_frontimg(string $media_filename, bool $image_thumb = true): string

// Responsive image with full options
invoke_responsive_image(
    string $media_filename,
    string $size = 'thumbnail', // 'thumbnail', 'medium', 'large'
    bool $image_thumb = true,
    string $alt = '',
    string $class = 'img-fluid',
    bool $fetchpriority = false,
    string $decoding = 'auto'
): string
```

#### Image Dimensions

| Size | Width | Height | Folder | Prefix |
|------|-------|--------|--------|--------|
| thumbnail | 640 | 450 | small/ | small_ |
| medium | 730 | 486 | medium/ | medium_ |
| large | 1200 | 630 | large/ | large_ |

#### Usage Examples

```php
// Basic featured image
echo invoke_frontimg('image123.jpg');

// Responsive image with specific size
echo invoke_responsive_image('image123.jpg', 'medium', true, 'My Image', 'img-fluid');

// Hero image for LCP optimization
echo invoke_hero_image('hero-image.jpg', '', 'Hero Title');

// Gallery image with lazy loading
echo invoke_gallery_image('gallery-1.jpg', 'Gallery Image');
```

#### Output Examples

**With WebP support:**
```html
<picture>
    <source srcset="https://example.com/public/files/pictures/image123.webp" type="image/webp">
    <img src="https://example.com/public/files/pictures/medium/medium_image123.jpg" alt="My Image" width="730" height="486" class="img-fluid" decoding="auto">
</picture>
```

**Without WebP (fallback):**
```html
<img src="https://example.com/public/files/pictures/medium/medium_image123.jpg" alt="My Image" width="730" height="486" class="img-fluid" decoding="auto">
```

#### Common Issues and Solutions

**1. esc_attr() Not Defined**
- Symptom: PHP error "Call to undefined function esc_attr()"
- Cause: Using WordPress function in theme files
- Solution: Replace with `htmlout()`

```php
// WRONG
esc_attr($value);

// CORRECT
htmlout($value);
```

**2. Empty src Attributes**
- Symptom: `<img src="">` in HTML output
- Cause: Incorrect path construction
- Solution: Always use APP_IMAGE constants or test path construction

**IMPORTANT:** When modifying image functions:
- Always use APP_IMAGE constants defined in lib/common.php
- Test changes on live site before committing
- Ask permission before changing existing working code

### Example: Using Utility Functions

```php
// In a controller or service

// Validate email
if (!email_validation($email)) {
    throw new \Exception("Invalid email");
}

// Sanitize output
$safeHtml = escape_html($userInput);

// Check CSRF token
if (!csrf_check_token($token)) {
    throw new \Exception("Invalid CSRF token");
}

// Get client IP
$ip = get_ip_address();
```

> **NOTE:** Always use utility functions for common operations. They are tested and follow security best practices.

---

## 12. Theming

### Theme Directory Structure

The default theme is located at `public/themes/blog/` and contains:

```
public/themes/blog/
├── theme.ini              # Theme metadata configuration
├── functions.php          # Theme functions and template tags
├── header.php            # Site header with navigation
├── footer.php            # Site footer with scripts and cookie consent
├── home.php              # Homepage template
├── single.php            # Single post view with comments
├── page.php              # Static page template
├── category.php          # Category archive template
├── tag.php               # Tag archive template
├── archive.php           # Monthly archive template
├── archives.php          # Archive index (all archives by year)
├── blog.php              # Blog listing page
├── sidebar.php           # Sidebar with widgets
├── comment.php           # Comment form (legacy)
├── privacy.php           # Privacy policy page template
├── 404.php               # 404 error page
├── cookie-consent.php    # GDPR cookie consent banner
├── download.php          # Download page template
├── download_file.php     # Download file handler
├── render-comments.php   # Comments rendering function
├── index.php             # Entry point (usually empty)
├── lang/                 # Language files
│   └── en.json          # English translations (i18n)
└── assets/               # Theme assets
    ├── css/             # Stylesheets
    ├── js/              # JavaScript files
    ├── vendor/          # Third-party libraries
    ├── fonts/           # Custom fonts
    └── img/             # Images
```

### theme.ini Configuration

```ini
[info]
theme_name = Bootstrap Blog
theme_designer = Ondrej Svetska
theme_description = Scriptlog default theme 
theme_directory = blog
```

### Theme Functions (functions.php)

The theme provides functions in the following categories:

#### i18n Functions

| Function | Description |
|----------|-------------|
| `t($key, $params)` | Translate a string |
| `locale_url($path, $locale)` | Get URL with locale prefix |
| `get_locale()` | Get current locale |
| `available_locales()` | Get available locales |
| `is_rtl()` | Check if current locale is RTL |
| `get_html_dir()` | Get HTML dir attribute |
| `language_switcher($args)` | Generate language switcher HTML |

#### Model Initialization Functions

| Function | Description |
|----------|-------------|
| `request_path()` | Get request path object |
| `initialize_post()` | Initialize PostModel |
| `initialize_page()` | Initialize PageModel |
| `initialize_comment()` | Initialize CommentModel |
| `initialize_archive()` | Initialize ArchivesModel |
| `initialize_topic()` | Initialize TopicModel |
| `initialize_tag()` | Initialize TagModel |
| `initialize_gallery()` | Initialize GalleryModel |

#### Post Retrieval Functions

| Function | Description |
|----------|-------------|
| `featured_post()` | Get random headline posts |
| `get_slideshow($limit)` | Get posts with media for slideshow |
| `sticky_page()` | Get random sticky page |
| `random_posts($start, $end)` | Get random posts |
| `latest_posts($limit, $position)` | Get latest posts |
| `retrieve_blog_posts()` | Get all published blog posts |
| `retrieve_detail_post($id)` | Get single post by ID |
| `posts_by_archive($values)` | Get posts by archive month/year |
| `archive_index()` | Get all archives for index |
| `posts_by_tag($tag)` | Get posts by tag |
| `searching_by_tag($tag)` | Full-text tag search |
| `posts_by_category($topicId)` | Get posts by category |
| `retrieve_page($arg, $rewrite)` | Get page by ID or slug |
| `retrieve_archives()` | Get archives for sidebar |

#### Navigation and Utility Functions

| Function | Description |
|----------|-------------|
| `front_navigation($parent, $menu)` | Render navigation menu recursively |
| `total_comment($id)` | Get total approved comments for post |
| `block_csrf()` | Generate CSRF token for comment form |
| `retrieves_topic_simple($id)` | Get topics for a post (simple) |
| `retrieves_topic_prepared($id)` | Get topics for a post (prepared) |
| `sidebar_topics()` | Get topics for sidebar |
| `retrieve_tags()` | Get tags for sidebar |
| `link_tag($id)` | Generate tag links for post |
| `link_topic($id)` | Generate topic links for post |
| `previous_post($id)` | Get previous post link |
| `next_post($id)` | Get next post link |
| `display_galleries($start, $limit)` | Get gallery images |
| `render_comments_section($postId, $offset)` | Render comments section HTML |
| `nothing_found()` | Display "no posts" message |
| `retrieve_site_url()` | Get site URL from config |
| `convert_menu_link($link, $permalinkEnabled)` | Convert menu link between SEO-friendly and query string formats |

### Navigation & i18n URL Compatibility

The theme navigation system properly adapts to both SEO-friendly URLs (permalinks enabled) and query string URLs (permalinks disabled), following the architecture defined in `I18N_ARCHITECTURE.md`.

#### URL Conversion Logic

| Permalink Status | Menu Links | Language Switcher |
|-----------------|------------|-------------------|
| **Disabled** | Query string (`?p=1`, `?pg=1`, etc.) | `?switch-lang=locale&redirect=...` |
| **Enabled** | SEO-friendly (`/post/1/slug`, `/page/slug`) | `locale_url()` with proper prefix |

#### locale_url() Behavior

The `locale_url()` function handles locale prefix based on settings:

```php
function locale_url(string $path = '', ?string $locale = null): string
{
    // When permalinks disabled: never add prefix
    if (!is_permalink_enabled()) {
        return $path;
    }
    
    // When permalinks enabled but prefix toggle off: no prefix for any language
    if (is_permalink_enabled() && !is_locale_prefix_enabled()) {
        return $path;
    }
    
    // When both permalinks and prefix enabled:
    // - Default language (en): no prefix
    // - Non-default language: add prefix (e.g., /es/post/1/slug)
    if ($targetLocale === $defaultLocale) {
        return $path;
    }
    
    return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
}
```

#### convert_menu_link() Function

This function converts menu links between formats based on permalink status:

```php
function convert_menu_link(string $link, bool $permalinkEnabled): string
{
    // Skip external links, anchors, and special links
    if (empty($link) || $link === '#' || strpos($link, '://') !== false) {
        return $link;
    }
    
    if ($permalinkEnabled) {
        // Convert query string to SEO-friendly format
        // ?p=1 -> /post/1/slug, ?pg=1 -> /page/slug, etc.
    } else {
        // Convert SEO-friendly to query string format
        // /post/1/slug -> ?p=1, /page/slug -> ?pg=ID, etc.
    }
}
```

#### theme_navigation() Locale Filtering

The `theme_navigation()` function filters menus by current locale:

```php
function theme_navigation($visibility)
{
    $currentLocale = get_locale();
    
    $sql = "SELECT ... FROM tbl_menu 
            WHERE menu_status = 'Y' 
              AND menu_visibility = ? 
              AND (menu_locale = ? OR menu_locale IS NULL OR menu_locale = '')
            ORDER BY menu_sort ASC, menu_label";
    // ...
}
```

This ensures only menus matching the current language (or menus with no specific locale) are displayed.

#### Language Switcher URL Format

The language switcher in `header.php` determines URL format based on permalink status:

```php
$permalinksEnabled = is_permalink_enabled() === 'yes';

if (!$permalinksEnabled) {
    // Query string format when permalinks disabled
    $lang_url = '?switch-lang=' . urlencode($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI']);
} else {
    // locale_url() when permalinks enabled
    $lang_url = locale_url($_SERVER['REQUEST_URI'], $locale);
}
```

### Theme Header (header.php)

The header includes:
- HTML doctype with language and direction attributes from i18n
- Meta tags (viewport, charset, SEO via theme_meta())
- RSS and Atom feed links
- Asset stylesheets (Bootstrap, Font Awesome, custom styles)
- Favicon
- Schema.org markup
- Navigation menu with collapsible mobile support (Sina Nav)

### Theme Footer (footer.php)

The footer includes:
- Copyright notice with dynamic year
- Template credits
- JavaScript assets (jQuery, Bootstrap, plugins)
- Cookie consent banner (GDPR)
- RTL script support

### Supported Features

| Feature | Implementation |
|---------|---------------|
| **RTL Support** | `is_rtl()` function, rtl.css, rtl.js |
| **Internationalization** | I18nManager class, lang/en.json |
| **Cookie Consent** | GDPR banner with API endpoint |
| **Comments** | AJAX loading, CSRF protection |
| **Download System** | UUID-based secure download links |
| **Archives** | Monthly archives with index |
| **Privacy Policy** | Static page template |
| **Responsive** | Bootstrap 4, mobile navigation |

### Asset Locations

| Asset Type | Location |
|------------|----------|
| Main CSS | `assets/css/style.sea.css` |
| Custom CSS | `assets/css/custom.css` |
| Cookie Consent | `assets/css/cookie-consent.css` |
| Comment CSS | `assets/css/comment.css` |
| RTL CSS | `assets/css/rtl.css` |
| 404 CSS | `assets/css/not-found.css` |
| Navigation CSS | `assets/css/sina-nav.css` |
| Front JS | `assets/js/front.js` |
| Comment JS | `assets/js/comment-submission.js`, `assets/js/load-comment.js` |
| Cookie JS | `assets/js/cookie-consent.js` |
| RTL JS | `assets/js/rtl.js` |
| Bootstrap | `assets/vendor/bootstrap/` |
| Font Awesome | `assets/vendor/font-awesome/` |
| jQuery | `assets/vendor/jquery/` |
| Fancybox | `assets/vendor/@fancyapps/fancybox/` |
| Popper.js | `assets/vendor/popper.js/` |

### Theme Template Files

#### home.php

The homepage template includes:
- Hero section with featured post background
- Intro section with sticky page content
- Plugin invocation for "Hello World" plugin
- Random posts section (alternating left/right layout)
- Divider section with featured content background
- Latest posts grid (3 columns)
- Gallery section with Fancybox lightbox

#### single.php

Single post template displays:
- Featured image
- Post title with permalink
- Author, date, and comment count metadata
- Post content with htmLawed HTML filtering
- Post tags
- Previous/next post navigation
- Comments section (AJAX-loaded)
- Comment form with CSRF protection

#### page.php

Static page template includes:
- Featured image
- Page title with permalink
- Author and date metadata
- Page content with HTML filtering
- Tags display

#### category.php, tag.php, archive.php, archives.php, blog.php

Archive templates share common structure:
- Topic/tag/archive header
- Post grid layout (2 columns)
- Post metadata (thumbnail, title, excerpt, author, date, comments)
- Sidebar inclusion
- Pagination

#### sidebar.php

Sidebar widgets:
- Search form
- Latest posts (5 posts with thumbnails)
- Categories list with post counts
- Archives list with post counts
- Tags cloud

#### 404.php

Simple 404 error page with:
- 404 display
- "Page not found" message
- Back to home link

#### privacy.php

Static privacy policy page with:
- Privacy policy content
- Last updated date
- Contact information
- Back to home button

#### cookie-consent.php

GDPR cookie consent banner with:
- Privacy notice text
- Accept/Reject/Learn More buttons
- Privacy policy link
- API endpoint for consent management

#### download.php, download_file.php

Download page templates:
- File information display
- Download button with UUID-based URL
- Copy link functionality
- Expiration countdown
- Optional support URL

### Creating Custom Themes

1. Create directory: `public/themes/[theme-name]/`
2. Copy required templates from blog theme:
   - `theme.ini` - Theme metadata
   - `functions.php` - Theme functions
   - `header.php` - Site header
   - `footer.php` - Site footer
   - `home.php` - Homepage
   - `single.php` - Post view
   - `page.php` - Page view
   - `category.php` - Category archive
   - `tag.php` - Tag archive
   - `archive.php` - Monthly archive
   - `archives.php` - Archive index
   - `blog.php` - Blog listing
   - `sidebar.php` - Sidebar
   - `404.php` - Error page
3. Create `theme.ini` with metadata
4. Add assets to `assets/` subdirectory
5. Register theme in admin panel (admin/templates.php)

> **TIP:** Use `public/themes/blog/` as a reference theme for creating new themes.

---

## 13. Plugins

> **NOTE:** For comprehensive plugin development documentation, see [PLUGIN_DEVELOPER_GUIDE.md](PLUGIN_DEVELOPER_GUIDE.md)

### Plugin Structure

```
admin/plugins/[plugin-name]/
|-- plugin.ini           # Required - plugin configuration
|-- YourClassFile.php    # Required - main plugin class
|-- functions.php        # Optional - helper functions
+-- schema.sql           # Optional - database schema
```

### plugin.ini Required Fields

```ini
[INFO]
plugin_name = "Plugin Name"
plugin_description = "Description of your plugin"
plugin_level = "administrator"  # or "manager"
plugin_version = "1.0.0"
plugin_author = "Author Name"
plugin_loader = "your-class-file"  # PHP class file (without .php)
plugin_action = "your-action"       # Action for routing
```

### Creating a Plugin

#### Step 1: Create Plugin Directory

```
admin/plugins/my-plugin/
```

#### Step 2: Create plugin.ini

```ini
[INFO]
plugin_name = "My Custom Plugin"
plugin_description = "A custom plugin for extending functionality"
plugin_level = "administrator"
plugin_version = "1.0.0"
plugin_author = "Developer Name"
plugin_loader = "MyPlugin"
plugin_action = "my-plugin"
```

#### Step 3: Create Main Plugin Class

```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

class MyPlugin
{
    private $pluginDir;
    
    public function __construct()
    {
        $this->pluginDir = dirname(__FILE__);
    }
    
    public function activate()
    {
        // Run on plugin activation
        // Create tables, set options, etc.
        return true;
    }
    
    public function deactivate()
    {
        // Run on plugin deactivation
        return true;
    }
    
    public function uninstall()
    {
        // Run on plugin deletion
        return true;
    }
    
    public function adminPage()
    {
        // Render admin page
        return '<div class="box">...</div>';
    }
    
    public function frontendDisplay($content = '')
    {
        // Modify frontend content
        return $content;
    }
    
    public function getInfo()
    {
        $iniFile = $this->pluginDir . DIRECTORY_SEPARATOR . 'plugin.ini';
        return file_exists($iniFile) ? parse_ini_file($iniFile) : [];
    }
}
```

#### Step 4: Create Optional Functions File

```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

function my_plugin_instance()
{
    static $instance = null;
    if (null === $instance) {
        $instance = new MyPlugin();
    }
    return $instance;
}

function my_plugin_display($content = '')
{
    return my_plugin_instance()->frontendDisplay($content);
}
```

#### Step 5: Create Optional SQL Schema

```sql
-- Create plugin-specific tables
CREATE TABLE IF NOT EXISTS tbl_my_plugin (
    ID BIGINT PRIMARY KEY AUTO_INCREMENT,
    data VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Include DROP statement for uninstall
-- DROP TABLE IF EXISTS tbl_my_plugin;
```

### Plugin Hook System

```php
// Register a hook
clip('hook_name', null, function($value) {
    return $value . ' modified';
});

// Execute hook
$result = clip('hook_name', 'original value');
```

### Plugin Utilities

Available in `lib/utility/plugin-validator.php`:

| Function | Description |
|----------|-------------|
| `validate_plugin_structure($pluginDir)` | Validate plugin directory structure |
| `validate_plugin_zip($zipPath)` | Validate plugin ZIP before upload |
| `get_plugin_info($pluginDir)` | Get plugin info from plugin.ini |
| `get_plugin_sql_file($pluginDir)` | Get SQL file path |
| `get_plugin_functions_file($pluginDir)` | Get functions.php path |

---

## 14. API Reference

### RESTful API Overview

ScriptLog provides a RESTful API that allows external applications to interact with blog content. The API follows OpenAPI 3.0 specification and returns JSON responses.

| Environment | URL |
|-------------|-----|
| **Production** | `http://ScriptLog.site/api/v1` |
| **Development** | `http://localhost/ScriptLog/public_html/api/v1` |

> **NOTE:** The complete OpenAPI 3.0 specification is available at `/docs/API_OPENAPI.json` and `/docs/API_OPENAPI.yaml`.

### API Version: 1.1.0

**Latest Enhancements (v1.1.0):**
- **Rate Limiting**: File-based sliding window rate limiter with per-client tracking
- **HATEOAS**: RFC 5988 Web Linking support — all responses include `_links` for discoverable navigation

### Rate Limiting

API requests are rate limited to ensure fair usage and prevent abuse. Rate limiting is applied per-client using IP address, API key, or Bearer token as the identifier.

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| **Read (GET)** | 60 requests | 60 seconds |
| **Write (POST/PUT/DELETE/PATCH)** | 20 requests | 60 seconds |

#### Rate Limit Headers

All API responses include rate limit headers:

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed per window |
| `X-RateLimit-Remaining` | Remaining requests in current window |
| `X-RateLimit-Reset` | Unix timestamp when the rate limit resets |
| `Retry-After` | Seconds to wait before retrying (only on 429 responses) |

#### Rate Limit Exceeded Response

```json
{
  "success": false,
  "status": 429,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Please slow down."
  }
}
```

#### Client Identification

Rate limits are tracked per client using the following priority:
1. **API Key** (`X-API-Key` header) — if provided
2. **Bearer Token** (`Authorization` header) — if provided
3. **IP Address** (`REMOTE_ADDR`) — fallback

### HATEOAS (Hypermedia as the Engine of Application State)

All API responses include HATEOAS links following [RFC 5988 (Web Linking)](https://tools.ietf.org/html/rfc5988). This allows clients to discover available actions dynamically without hardcoding URLs.

#### Response Structure

Every response includes a `_links` object:

```json
{
  "success": true,
  "status": 200,
  "data": { ... },
  "_links": {
    "self": {
      "href": "http://blogware.site/api/v1/posts/1",
      "rel": "self",
      "type": "GET"
    },
    "collection": {
      "href": "http://blogware.site/api/v1/posts",
      "rel": "collection",
      "type": "GET"
    }
  }
}
```

#### Common Link Relations

| Relation | Description |
|----------|-------------|
| `self` | The current resource URL |
| `collection` | The parent collection URL |
| `first` | First page of paginated results |
| `prev` | Previous page of paginated results |
| `next` | Next page of paginated results |
| `last` | Last page of paginated results |
| `canonical` | The canonical HTML URL for the resource |
| `comments` | Comments for a post |
| `post` | The parent post for a comment |
| `posts` | Posts in a category |
| `year` | Year archive for a month |
| `search` | Search endpoint (templated URL) |
| `service-desc` | OpenAPI specification URL |

#### Root API Links

The API root (`GET /api/v1/`) returns links to all available endpoints:

```json
{
  "_links": {
    "self": { "href": "/api/v1", "rel": "self", "type": "GET" },
    "posts": { "href": "/api/v1/posts", "rel": "posts", "type": "GET" },
    "categories": { "href": "/api/v1/categories", "rel": "categories", "type": "GET" },
    "comments": { "href": "/api/v1/comments", "rel": "comments", "type": "GET" },
    "archives": { "href": "/api/v1/archives", "rel": "archives", "type": "GET" },
    "search": { "href": "/api/v1/search?q={query}", "rel": "search", "type": "GET", "templated": true },
    "openapi": { "href": "/api/v1/openapi.json", "rel": "service-desc", "type": "application/json" }
  }
}
```

### Authentication

The API supports two authentication methods:

#### API Key Authentication

```
GET /api/v1/posts HTTP/1.1
Host: ScriptLog.site
X-API-Key: your-api-key-here
```

#### Bearer Token Authentication

```
GET /api/v1/posts HTTP/1.1
Host: ScriptLog.site
Authorization: Bearer your-token-here
```

#### Permission Levels

| Level | Create Posts | Edit Posts | Delete Posts | Manage Categories | Moderate Comments |
|-------|-------------|-----------|--------------|------------------|-------------------|
| **administrator** | Yes | Yes | Yes | Yes | Yes |
| **editor** | Yes | Yes | No | Yes | Yes |
| **author** | Yes | Own only | No | No | No |
| **subscriber** | No | No | No | No | No |

### API Endpoints

#### Posts API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/posts` | No | List published posts |
| `GET` | `/api/v1/posts/{id}` | No | Get single post |
| `GET` | `/api/v1/posts/{id}/comments` | No | Get post comments |
| `POST` | `/api/v1/posts` | Yes | Create post |
| `PUT` | `/api/v1/posts/{id}` | Yes | Update post |
| `DELETE` | `/api/v1/posts/{id}` | Yes | Delete post |

#### Categories API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/categories` | No | List categories |
| `GET` | `/api/v1/categories/{id}` | No | Get category |
| `GET` | `/api/v1/categories/{id}/posts` | No | Get posts in category |
| `POST` | `/api/v1/categories` | Yes | Create category |
| `PUT` | `/api/v1/categories/{id}` | Yes | Update category |
| `DELETE` | `/api/v1/categories/{id}` | Yes | Delete category |

#### Comments API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/comments` | No | List approved comments |
| `GET` | `/api/v1/comments/{id}` | No | Get comment |
| `POST` | `/api/v1/comments` | No | Submit comment |
| `PUT` | `/api/v1/comments/{id}` | Yes | Update comment |
| `DELETE` | `/api/v1/comments/{id}` | Yes | Delete comment |

#### Archives API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/archives` | No | List archive dates |
| `GET` | `/api/v1/archives/{year}` | No | Posts from year |
| `GET` | `/api/v1/archives/{year}/{month}` | No | Posts from month |

### Query Parameters

All list endpoints support:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Items per page (max: 100) |
| `sort_by` | string | ID | Sort field |
| `sort_order` | string | DESC | Sort direction (ASC/DESC) |

### Response Format

#### Success Response

```json
{
  "success": true,
  "status": 200,
  "message": "Operation description",
  "data": { ... }
}
```

#### Paginated Response

```json
{
  "success": true,
  "status": 200,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_items": 50,
    "total_pages": 5,
    "has_next_page": true,
    "has_previous_page": false
  }
}
```

#### Error Response

```json
{
  "success": false,
  "status": 400,
  "error": {
    "code": "BAD_REQUEST",
    "message": "Error description"
  }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Unprocessable Entity |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

### Creating API Controllers

#### Step 1: Create Controller

```php
// lib/controller/api/MyResourceApiController.php
<?php
class MyResourceApiController extends ApiController
{
    private $resourceDao;
    
    public function __construct()
    {
        parent::__construct();
        $this->resourceDao = new MyResourceDao();
    }
    
    public function index($params = [])
    {
        $this->requiresAuth = false;
        $pagination = $this->getPagination($params);
        
        try {
            $resources = $this->resourceDao->findAll($pagination);
            $total = $this->resourceDao->count();
            ApiResponse::paginated($resources, $pagination['page'], $pagination['per_page'], $total);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'FETCH_ERROR');
        }
    }
    
    public function show($params = [])
    {
        $id = isset($params[0]) ? (int)$params[0] : 0;
        
        if (!$id) {
            ApiResponse::badRequest('ID is required');
            return;
        }
        
        $resource = $this->resourceDao->findById($id);
        
        if (!$resource) {
            ApiResponse::notFound('Resource not found');
            return;
        }
        
        ApiResponse::success($resource);
    }
    
    public function store($params = [])
    {
        $this->requiresAuth = true;
        
        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }
        
        $validationErrors = $this->validateRequired($this->requestData, ['name']);
        
        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }
        
        $id = $this->resourceDao->create($this->requestData);
        ApiResponse::created(['id' => $id], 'Created successfully');
    }
}
```

#### Step 2: Register Routes

```php
// api/index.php
$router->get('resources', 'MyResourceApiController@index');
$router->get('resources/([0-9]+)', 'MyResourceApiController@show');
$router->post('resources', 'MyResourceApiController@store');
$router->put('resources/([0-9]+)', 'MyResourceApiController@update');
$router->delete('resources/([0-9]+)', 'MyResourceApiController@destroy');
```

---

## 15. Testing

> **NOTE:** For comprehensive testing documentation including PHPStan setup and CI/CD integration, see [TESTING_GUIDE.md](TESTING_GUIDE.md).

### Testing Overview

This project uses two complementary testing approaches:

| Tool | Purpose | Coverage |
|------|---------|----------|
| **PHPUnit** | Unit and integration testing | Functional correctness |
| **PHPStan** | Static code analysis | Type safety, code quality |

### Test Suite Metrics

| Metric | Value |
|--------|-------|
| **Total Tests** | 1,172 |
| **Test Files** | 73 |
| **Assertions** | ~1300+ |
| **PHPUnit Version** | 9.6.34 |
| **Target Coverage** | 40% |
| **Current Coverage** | ~35% |

### Test Coverage Plan

The test coverage plan is organized into phases:

#### Phase Status

| Phase | Priority | Status | Tests |
|-------|----------|--------|-------|
| Phase 1: DAO Integration | HIGH | ✅ Complete | 92 |
| Phase 2: Service Layer | HIGH | ✅ Complete | 148 |
| Phase 3: Core Classes | MEDIUM | 🔄 Pending | 65 |
| Phase 4: Controllers | MEDIUM | 🔄 Pending | 34 |
| Phase 5: Utilities | LOW | ✅ Complete | 68 |
| Password Protected Posts | HIGH | ✅ Complete | 59 |

### Test Categories

| Category | Description |
|----------|-------------|
| **Unit Tests** | Utility function tests, class existence tests |
| **Integration Tests** | Database CRUD operations using `blogware_test` database |

### Security Testing

PostDao security tests verify critical security features:

| Test | Purpose |
|------|---------|
| `testFindPostsHasOnlyPublishedParameter` | Verifies default filters for published posts only |
| `testFindPostHasOnlyPublishedParameter` | Verifies single post retrieval filters for published posts |
| `testFindPostsHasAuthorParameter` | Verifies author filtering support |
| `testFindPostsHasSanitizedOrderBy` | Verifies ORDER BY uses whitelist to prevent SQL injection |
| `testFindPostsFiltersByStatusAndVisibility` | Verifies post_status and post_visibility filters |
| `testFindPostFiltersByStatusAndVisibility` | Verifies single post respects status/visibility |

**Location**: `tests/unit/PostDaoSecurityTest.php`

### Password-Protected Posts Testing

Comprehensive tests for the password-protected posts system:

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `tests/unit/ProtectedPostTest.php` | 12 | Core encryption/decryption functions |
| `tests/unit/ProtectedPostRateLimitTest.php` | 20 | Rate limiting & password strength |
| `tests/unit/PostControllerProtectedPostTest.php` | 27 | Controller flow & validation |

**Total: 59 tests**

| Test Category | Tests |
|--------------|-------|
| Rate Limiting Logic | 9 (5 attempts limit, old expiration, per-IP/per-post) |
| Password Strength | 8 (length, uppercase, lowercase, number, special char) |
| Functions Existence | 1 |
| Session Storage | 3 |
| Encryption/Decryption | 4 |
| Visibility Validation | 4 |
| Form Validation | 6 |
| CSRF Protection | 1 |
| Required Fields | 2 |

Run password-protected posts tests:
```bash
php lib/vendor/bin/phpunit tests/unit/ProtectedPost*.php --bootstrap tests/bootstrap.php
```

### Running Tests

#### PHPUnit Commands

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

#### PHPStan Commands

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

### Static Analysis with PHPStan

PHPStan is a static analysis tool that finds bugs in your code without running it.

#### Configuration Files

| File | Purpose |
|------|---------|
| `phpstan.neon` | Main configuration |
| `phpstan.baseline.neon` | Baseline of known issues to ignore |

#### PHPStan Configuration

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

#### Key Settings

- **phpVersion**: Set to `70400` for PHP 7.4 compatibility
- **level**: Currently at level 0 (most lenient). Increase gradually for stricter checks
- **excludePaths**: Excludes vendor and third-party code

### Test Database Setup

Tests use a separate database (`blogware_test`) to avoid affecting production data.

```bash
# Create test database
php tests/setup_test_db.php

# Or manually
mysql -u root -p -e "CREATE DATABASE blogware_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

### Writing Tests

#### PHPUnit Test Structure

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

#### Best Practices

1. **Test one thing per method** - Each test should verify a single behavior
2. **Use descriptive names** - Method names should describe what is being tested
3. **Arrange-Act-Assert** - Structure tests with clear setup, action, and verification phases
4. **Mock external dependencies** - Use mocks for database, filesystem, etc.

### CI/CD Integration

#### GitHub Actions Example

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

#### Pre-commit Hook

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash
lib/vendor/bin/phpstan analyse --memory-limit=1G
lib/vendor/bin/phpunit
```

### Troubleshooting

#### PHPUnit Issues

| Issue | Solution |
|-------|----------|
| Tests fail with "Database not found" | Run `php tests/setup_test_db.php` |
| Xdebug required for coverage | Install Xdebug or skip coverage |

#### PHPStan Issues

| Issue | Solution |
|-------|----------|
| Memory limit exceeded | Run with `--memory-limit=1G` |
| Too many errors | Use baseline or increase level gradually |
| False positives | Add to ignoreErrors in phpstan.neon |
| Missing bleedingEdge.neon | Remove from includes in phpstan.neon |

### Recently Added Tests

#### Medoo and Membership Utilities Tests (April 2026)
- `tests/unit/MedooinFunctionsTest.php` (26 tests) - Tests for `is_medoo_database()`, `is_db_database()`, `db_build_where()`, `medoo_select()`, `medoo_insert()`, `medoo_update()`, `medoo_delete()`
- `tests/integration/MedooinIntegrationTest.php` (8+ tests) - Integration tests for database selection and operations
- `tests/unit/MembershipFunctionsTest.php` (26 tests) - Tests for `is_registration_unable()`, `membership_default_role()`, `membership_get_role()`, `membership_get_role_name()`
- `tests/integration/MembershipIntegrationTest.php` (8 tests) - Integration tests for membership settings

#### PostDao Security Tests (April 2026)
- `tests/unit/PostDaoSecurityTest.php` (6 tests) - Verifies SQL injection prevention and security filters

---

## 16. Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| **Session not starting** | Check `SessionMaker` is properly initialized in Bootstrap |
| **Database connection failed** | Verify `config.php` has correct credentials |
| **404 on valid routes** | Check `.htaccess` rewrite rules |
| **CSRF errors** | Ensure `csrf-defender.php` is loaded and tokens are passed |

### Debug Mode

```php
// In config.php or common.php
define('APP_DEVELOPMENT', true);

// Enable error reporting
if (APP_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```

> **WARNING:** Never enable debug mode in production as it may expose sensitive information.

---

## 17. Asset Management

### UI Asset Locations

| Location | Purpose |
|----------|---------|
| `admin/assets/` | Admin panel CSS, JS, images |
| `public/themes/blog/assets/` | Blog theme CSS, JS, images |

### Known Active Assets

**Admin Panel (admin/assets/):**
- `dist/css/AdminLTE.min.css` - Main theme
- `dist/css/skins/scriptlog-skin.css` - Active skin
- `dist/css/rtl.css` - RTL language support
- `components/bootstrap/dist/css/bootstrap.min.css`
- `components/font-awesome/css/font-awesome.min.css`

**Blog Theme (public/themes/blog/assets/):**
- `css/style.sea.css` - Main theme style
- `css/sina-nav.css` - Navigation styles
- `vendor/@fancyapps/fancybox/jquery.fancybox.min.css` - Lightbox
- `vendor/bootstrap/css/bootstrap.min.css`
- `vendor/font-awesome/css/font-awesome.min.css`

### Asset Cleanup Best Practices

**Before deleting any asset files:**

1. **Read template files** that include assets:
   - `admin/admin-layout.php` - Admin header template
   - `public/themes/blog/header.php` - Theme header template
   - `public/themes/blog/footer.php` - Theme footer template

2. **Search for references** using grep:
   ```bash
   grep -r "stylesheet\|script.*src" admin/ public/themes/
   ```

3. **Verify all files exist** before cleanup:
   ```bash
   ls -la path/to/asset.css
   ```

**Files that are safe to remove:**
- Non-minified `.css`/`.js` files when minified versions exist
- Duplicate libraries in different formats
- Reference documentation files (e.g., `icons-reference/`)
- License files in vendor directories

**Files to NEVER remove without verification:**
- Files referenced in layout templates
- Minified versions (they're typically what's used)
- Skin files actively used by the theme

---

## Key Constants

```php
// Paths
APP_ROOT           // Application root path
APP_ADMIN          // 'admin'
APP_PUBLIC         // 'public'
APP_LIBRARY        // 'lib'
APP_THEME          // 'public/themes'
APP_PLUGIN         // 'admin/plugins'
APP_IMAGE          // 'public/files/pictures'
APP_VIDEO          // 'public/files/video'
APP_AUDIO          // 'public/files/audio'

// Security
SCRIPTLOG          // Security constant (HMAC hash)

// Settings
APP_TITLE          // 'Scriptlog'
APP_VERSION        // '1.0'
APP_DEVELOPMENT    // true/false
```

## Key Classes

| Category | Classes |
|----------|---------|
| **Core** | Bootstrap, Dispatcher, DbFactory, Authentication, SessionMaker, Registry, FormValidator, Sanitize, View |
| **DAO** | PostDao, UserDao, CommentDao, ReplyDao, TopicDao, MediaDao, PageDao, MenuDao, PluginDao, ThemeDao, ConfigurationDao, ConsentDao |
| **Service** | PostService, UserService, CommentService, ReplyService, TopicService, MediaService, PageService, MenuService, PluginService, ThemeService, ConsentService, DownloadService |
| **Controller** | PostController, UserController, CommentController, ReplyController, TopicController, MediaController, PageController, MenuController, PluginController, ThemeController, DownloadController, DownloadAdminController |
| **Utility** | DownloadHandler, DownloadSettings |

## Global Functions

```php
// Session
start_session_on_site($sessionMaker);
regenerate_session();

// Security
csrf_check_token($token);
remove_xss($data);
escape_html($html);
sanitize_urls($url);
forbidden_direct_access();

// Validation
email_validation($email);
url_validation($url);
form_id_validation($id);

// Utility
get_ip_address();
app_url();
app_info();
theme_identifier();
invoke_frontimg($filename, $size = 'medium');
```

---

## Dependencies

### Required Packages

| Package | Version | Purpose | Used By |
|---------|---------|---------|---------|
| `sinergi/browser-detector` | ^6.1 | Device/browser detection | `lib/utility/get-os.php`, `install/include/check-engine.php` |
| `intervention/image` | ^2.5 | Image manipulation | `lib/utility/upload-photo.php` |
| `ircmaxell/random-lib` | ^1.2 | Secure random generation | `lib/core/Authentication.php`, `lib/core/CSRFGuard.php`, `lib/core/Tokenizer.php` |
| `egulias/email-validator` | ^2.1 | Email validation | `lib/utility/email-validation.php`, `lib/controller/UserController.php` |
| `voku/anti-xss` | ^4.1 | XSS prevention | `lib/utility/remove-xss.php` |
| `defuse/php-encryption` | ^2.2 | Data encryption | `lib/core/Authentication.php`, `lib/core/ScriptlogCryptonize.php` |
| `filp/whoops` | ^2.9 | Error handling | `lib/utility/whoops-error.php` |
| `psr/log` | ^1.1 | Logging interface | Dependency |
| `melbahja/seo` | ^2.0 | SEO optimization | `lib/utility/on-page-optimization.php`, `lib/core/BlogSchema.php` |
| `laminas/laminas-escaper` | ^2.12 | HTML escaping | `lib/utility/escape-html.php` |
| `laminas/laminas-crypt` | ^3.3 | Cryptography | `lib/core/ScriptlogCryptonize.php` |
| `laminas/laminas-feed` | ^2.17 | RSS/Atom feeds | `lib/core/AtomWriter.php`, `lib/core/RSSWriter.php` |
| `catfan/medoo` | ^2.1 | Database ORM | `lib/core/MedooInit.php` |

### Package Usage Examples

```php
// Browser detection
use Sinergi\BrowserDetector\Os;
$os = new Os();

// Email validation
use Egulias\EmailValidator\EmailValidator;
$validator = new EmailValidator();

// Image manipulation
use Intervention\Image\ImageManager;
$manager = new ImageManager();

// XSS prevention
use voku\helper\AntiXSS;
$antiXss = new AntiXSS();

// Encryption
use Defuse\Crypto\Crypto;
$encrypted = Crypto::encrypt($message, $key);

// SEO Meta Tags
use Melbahja\Seo\MetaTags;
$meta = new MetaTags();

// Feed generation
use Laminas\Feed\Writer\Feed;
$feed = new Feed();

// Database
use Medoo\Medoo;
$db = new Medoo($config);

// HTML Escaping
use Laminas\Escaper\Escaper;
$escaper = new Escaper('utf-8');
```

---

## 18. GDPR Compliance

### Overview

ScriptLog includes built-in GDPR compliance features designed to handle user consent, data subject requests, and automated privacy auditing. This section documents the architecture and implementation of these features.

### Admin Page Authorization

All admin pages containing sensitive operations (especially GDPR features) must implement proper authentication checks to prevent unauthorized access to personal data:

```php
// admin/privacy.php - Example of proper authorization
if (false === $authenticator->userAccessControl(ActionConst::PRIVACY)) {
    direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
}
```

Available permissions:
- `ActionConst::PRIVACY` - Privacy settings, GDPR data requests, audit logs.
- `ActionConst::USERS` - User management and profile deletion.

### Database Tables

The GDPR system relies on three core tables for consent, requests, and auditing:

**1. tbl_consents** - Stores user choices for cookies and tracking.
```sql
CREATE TABLE tbl_consents (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    consent_type VARCHAR(50) NOT NULL,
    consent_status ENUM('accepted','rejected') NOT NULL,
    consent_ip VARCHAR(45) NOT NULL,
    consent_user_agent VARCHAR(255) DEFAULT NULL,
    consent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID)
);
```

**2. tbl_data_requests** - Tracks data export and deletion requests.
```sql
CREATE TABLE tbl_data_requests (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    request_type VARCHAR(50) NOT NULL,
    request_email VARCHAR(100) NOT NULL,
    request_status ENUM('pending','processing','completed','rejected') DEFAULT 'pending',
    request_ip VARCHAR(45) NOT NULL,
    request_note TEXT DEFAULT NULL,
    request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_completed_date DATETIME DEFAULT NULL,
    PRIMARY KEY (ID)
);
```

**3. tbl_privacy_logs** - Automated audit trail for all privacy-related actions.
```sql
CREATE TABLE tbl_privacy_logs (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    log_action VARCHAR(50) NOT NULL,
    log_type VARCHAR(50) NOT NULL,
    log_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
    log_email VARCHAR(100) DEFAULT NULL,
    log_details TEXT DEFAULT NULL,
    log_ip VARCHAR(45) NOT NULL,
    log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID)
);
```

### Core Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `ConsentService` | `lib/service/ConsentService.php` | Manages user consent records. |
| `DataRequestService` | `lib/service/DataRequestService.php` | Handles data exports and anonymization logic. |
| `NotificationService` | `lib/service/NotificationService.php` | Orchestrates automated compliance emails. |
| `PrivacyLogDao` | `lib/dao/PrivacyLogDao.php` | Records audit trails for privacy actions. |

### Data Subject Requests

#### 1. Data Export
Administrators can process export requests via `DataRequestService::exportUserData()`. This method:
- Aggregates user profile data, comments, posts, and activity logs.
- Generates a structured JSON file for the user.
- Logs the export event to the privacy audit trail.

#### 2. Data Deletion & Anonymization
To respect the "Right to be Forgotten," ScriptLog uses an anonymization approach rather than hard deletion to preserve database integrity:
- **Comments**: Name, email, and IP are anonymized.
- **Posts**: Reassigned to the primary administrator (ID: 1).
- **Profile**: Email is changed to a unique placeholder (`deleted_ID@user.local`).
- **Automation**: Managed via `UserService::removeUserWithAnonymization()`.

### Automated Email Notifications

The system sends automated notifications during the compliance lifecycle:
- **Confirmation**: Sent to the user when a request is received.
- **Admin Alert**: Notifies administrators of new pending requests.
- **Completion**: Sent when data has been exported or anonymized.
- **Transport**: Powered by the **Dynamic SMTP System** using Symfony Mailer.

### Cookie Consent Banner

The frontend provides a standard consent interface:
- **Banner**: `public/themes/blog/cookie-consent.php`.
- **Logic**: `public/themes/blog/assets/js/cookie-consent.js`.
- **Persistence**: Choices are stored in both cookies (frontend) and `tbl_consents` (backend).

### GDPR API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/gdpr/consent` | Record user consent choice. |
| `GET` | `/api/v1/gdpr/consent` | Retrieve current consent status. |

### Implementation Workflow

To add new compliance features:
1.  **Define Table**: Add to `install/include/dbtable.php`.
2.  **Service Logic**: Implement in `lib/service/`.
3.  **Audit Trail**: Call `PrivacyLogDao::createLog()` for every sensitive action.
4.  **Notification**: Use `NotificationService` to inform users of the action.
5.  **UI**: Add management forms to `admin/ui/privacy/`.

### Testing Compliance

```bash
# Verify privacy page accessibility
curl -I https://example.com/privacy

# Test automated logging
# Perform a data export in Admin UI and check tbl_privacy_logs
```

---

## 19. Internationalization (i18n)

### Overview

ScriptLog includes a comprehensive i18n system for multi-language support, including:
- Language detection from browser, URL, or user preference
- Database-driven translation management
- RTL (Right-to-Left) language support
- Translation caching for performance
- RESTful API for managing languages and translations

### Architecture

```
+---------------------------------------------------------------+
|                     i18n REQUEST FLOW                         |
+---------------------------------------------------------------+
|                                                               |
|   Request                                                     |
|     |                                                         |
|     v                                                         |
|   +---------------------+                                     |
|   | LocaleDetector      |  Detect locale from:                |
|   |                     |  - URL prefix (/ar/, /es/)          |
|   +----------+----------+  - Cookie (lang)                    |
|              |             - Accept-Language header           |
|              |             - Default (en_US)                  |
|              v                                                |
|   +---------------------+                                     |
|   | I18nManager         |  Load translations & manage locale  |
|   +----------+----------+                                     |
|              |                                                |
|              v                                                |
|   +---------------------+                                     |
|   | TranslationLoader   |  Load from:                         |
|   +----------+----------+  - Database (tbl_translations)      |
|              |             - Cache file                       |
|              v                                                |
|   +---------------------+                                     |
|   | View/Theme          |  Output with lang/dir attributes    |
|   +---------------------+                                     |
|                                                               |
+---------------------------------------------------------------+
```

### Core Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `I18nManager` | `lib/core/I18nManager.php` | Main i18n orchestrator |
| `LocaleDetector` | `lib/core/LocaleDetector.php` | Language detection |
| `LocaleRouter` | `lib/core/LocaleRouter.php` | URL-based routing |
| `TranslationLoader` | `lib/core/TranslationLoader.php` | Translation loading/caching |
| `LanguageDao` | `lib/dao/LanguageDao.php` | Language CRUD |
| `TranslationDao` | `lib/dao/TranslationDao.php` | Translation CRUD |
| `LanguageService` | `lib/service/LanguageService.php` | Language business logic |
| `TranslationService` | `lib/service/TranslationService.php` | Translation business logic |

### Database Tables

**tbl_languages** - Supported languages

```sql
CREATE TABLE IF NOT EXISTS tbl_languages (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL UNIQUE,
    language_name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,
    is_rtl TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    KEY locale(locale),
    KEY is_active(is_active)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

**tbl_translations** - Translation strings

```sql
CREATE TABLE IF NOT EXISTS tbl_translations (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    context VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY unique_key_locale(locale, translation_key),
    KEY locale(locale),
    KEY translation_key(translation_key)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Language Detection Priority

1. **URL Prefix** - `/ar/`, `/es/`, `/fr/` (e.g., `example.com/ar/posts`)
2. **Cookie** - `lang` cookie set by language switcher
3. **Accept-Language Header** - Browser's language preference
4. **Default** - `en_US` (configurable)

### URL Routing for Languages

Languages are handled via URL prefixes in the existing routing system:

```php
// lib/core/Bootstrap.php
$rules = [
    // ... existing rules
    'language_blog' => "/{lang}/blog",
    'language_single' => "/{lang}/post/(?'id'\d+)/(?'post'[\w\-]+)",
    'language_page' => "/{lang}/page/(?'page'[^/]+)",
    'language_category' => "/{lang}/category/(?'category'[\w\-]+)",
    'language_tag' => "/{lang}/tag/(?'tag'[\w\-]+)",
];
```

### Translation Functions

```php
// Basic translation
__('Hello World');           // Returns translated string
__('Welcome, %s', [$name]); // With placeholders

// Echo translation
_e('Submit');                // Echoes translated string

// With context
_x('Read', 'verb');          // Context disambiguates same key
_ex('Read', 'book title');   // Echo with context

// Plural forms
_n('%d comment', '%d comments', $count);  // Returns correct form
```

### RTL Support

RTL languages (Arabic, Hebrew, Farsi, etc.) are automatically detected and styled:

```php
// Automatic detection based on language
$isRtl = $i18nManager->isRtl();  // true for ar, he, fa, etc.

// Theme files include RTL CSS
// public/themes/blog/assets/css/rtl.css
// public/themes/blog/assets/js/rtl.js
```

### i18n API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/languages` | No | List all languages |
| `GET` | `/api/v1/languages/active` | No | List active languages |
| `GET` | `/api/v1/languages/{locale}` | No | Get language details |
| `GET` | `/api/v1/translations` | No | Get translations for locale |
| `POST` | `/api/v1/languages` | Yes | Create language |
| `PUT` | `/api/v1/languages/{locale}` | Yes | Update language |
| `POST` | `/api/v1/translations` | Yes | Create translation |
| `PUT` | `/api/v1/translations/{key}` | Yes | Update translation |

### Creating i18n Features

1. **Add Language**: Use API or admin panel
2. **Add Translations**: Insert into `tbl_translations` with locale and key
3. **Use in Code**: Call translation functions
4. **Theme Support**: Ensure templates use translation functions

### Translation Caching

Translations are cached for performance:

- **Cache Location**: `public/files/cache/translations/`
- **Cache Format**: `translations_{locale}.json`
- **Cache Invalidation**: On translation update via API

### Adding New Translatable Content

When adding new features that need translation:

1. Use translation functions in templates:
```php
<h1><?= __('Welcome Message'); ?></h1>
```

2. Add translations via API:
```bash
curl -X POST https://example.com/api/v1/translations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"locale": "es", "key": "welcome_message", "value": "Bienvenido"}'
```

### Testing i18n

```bash
# Test language detection
curl -H "Accept-Language: es" http://example.com/

# Test translation API
curl http://example.com/api/v1/translations?locale=es

# Test RTL rendering
curl http://example.com/ar/ | grep 'dir="rtl"'
```

### Admin Panel Translations

Admin panel uses a **hybrid translation system** via `lib/utility/admin-translations.php`:

```
Translation Request Flow:
  admin_translate('key') 
    → Check database (tbl_translations) first
    → If found, return database value
    → If not found, check hardcoded arrays
    → Return fallback or key
```

```php
// Usage in admin views
admin_translate('nav.dashboard');      // "Dashboard"
admin_translate('form.save');          // "Save"
admin_translate('status.publish');     // "Published"

// With parameter interpolation
admin_t('welcome_message', ['name' => 'John']); // "Welcome, John"

// Locale management
admin_get_locale();    // Get current locale
admin_set_locale('ar'); // Set locale
admin_is_rtl();        // Check RTL (true for Arabic)
```

**Key format**: Dot-notation with underscore separators (e.g., `nav.dashboard`, `form.save`, `status.publish`)

The hybrid approach allows translations to be:
1. Managed via admin UI (Settings → Translations)
2. Stored in database for easy editing
3. Fallback to hardcoded arrays if not in database

### Translation Editor

The admin panel includes a translation editor at **Settings → Translations**:

- **View**: Table listing all translations with filtering
- **Add New**: Add new translation keys via modal form
- **Edit**: Modify existing translations via modal form  
- **Delete**: Remove translations (POST with CSRF protection)
- **Export**: Download translations as JSON
- **Import**: Upload translations from JSON
- **Cache**: Regenerate translation cache
- **Language Selector**: Switch between languages or view all

### Common Issues and Fixes

#### 1. Database Connection Charset (CRITICAL)

The PDO database connection MUST use `charset=utf8mb4` in the DSN to properly load translations in non-English languages (Chinese, Arabic, etc.).

**Files to check:**
- `lib/core/Bootstrap.php` - Database DSN configuration
- `lib/core/Db.php` - PDO connection options

**Correct DSN format:**
```php
$dbc = DbFactory::connect([
    'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4',
    $user,
    $pwd
]);
```

**WRONG (will show "???" for Chinese/Arabic):**
```php
'mysql:host=...;dbname=...'
```

#### 2. Translation Value Standards

**Human-readable first**: Translation values should be natural, complete phrases in the target language, not abbreviations or technical terms.

```
✅ Good:   "Choose your language", "Add New", "All Posts", "Error Server Error"
❌ Bad:    "Language Settings", "addNew", "allPosts", "Error serverError"
```

**Example** - `nav.language_settings`:
| Language | Value |
|----------|-------|
| en | Choose your language |
| ar | اختر لغتك |
| zh | 选择您的语言 |
| fr | Choisissez votre langue |
| ru | Выберите язык |
| es | Elige tu idioma |
| id | Pilih bahasa Anda |

When updating translations in the database, always clear the cache:

```php
// Clear translation cache after database updates
$cacheFile = 'public/files/cache/translations/' . $locale . '.json';
@unlink($cacheFile);
// System will regenerate on next request
```

#### 3. Translation Database Fixes

When translations in the database show incorrect values (like "Nav addNew" instead of actual translations), fix directly via SQL:

**Check broken translations:**
```sql
SELECT * FROM tbl_translations 
WHERE translation_value LIKE 'Nav %'
```

#### 4. Language Selector Not Working

The Translation Editor language dropdown must work with the session-based locale system:

**Flow:**
1. User selects language in dropdown → JavaScript redirects with `?switch-lang=id`
2. `admin/index.php` processes `switch-lang` parameter → calls `admin_set_locale('id')`
3. `admin_set_locale()` saves to `$_SESSION['admin_locale']` and cookie
4. Translation Editor uses `admin_get_locale()` to determine which translations to show

**Key files:**
- `admin/index.php` - Handles `switch-lang` parameter
- `lib/utility/admin-translations.php` - `admin_get_locale()` and `admin_set_locale()` functions
- `lib/controller/TranslationController.php` - Uses `admin_get_locale()` when `$_GET['lang']` not set

**TranslationController locale logic (CORRECT):**
```php
if (isset($_GET['lang']) && $_GET['lang'] === 'all') {
    $langCode = 'all';
} elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'])) {
    $langCode = $_GET['lang'];
} else {
    // Fall back to session/cookie locale
    $langCode = admin_get_locale();
}
```

#### 5. Translation Editor URL Parameters

The Translation Editor uses these URL parameters:
- `?load=translations` - Main page
- `?load=translations&lang=en` - Show English translations
- `?load=translations&lang=id` - Show Indonesian translations  
- `?load=translations&lang=all` - Show all languages (with pagination)
- `?load=translations&action=update` - Update translation (POST)
- `?load=translations&action=new-translation` - Create translation (POST)

### Adding Content i18n Support

To add locale support to a new content type:

1. **Database**: Add `content_locale` column to table
2. **Dao**: Add `dropDownLocale()` method
3. **Service**: Add `setContentLocale()` method
4. **Controller**: Add locale filters and setters
5. **Admin UI**: Add locale dropdown to edit form

### Populating Languages and Translations

The system includes:
- 7 languages (en, ar, zh, fr, ru, es, id)
- 111 translation keys with 819 total translations
- Translation editor in admin panel (Settings → Translations)
- Translation cache in `public/files/cache/translations/`

Use the admin panel (Settings → Languages and Settings → Translations) to manage languages and translations.

### Configuration

Default language settings are in `lib/core/I18nManager.php`:

```php
private $defaultLocale = 'en_US';
private $supportedLocales = ['en_US', 'ar', 'es', 'fr', 'de', 'zh_CN'];
```

### Documentation

For comprehensive API documentation and testing, see:
- `docs/I18N_ARCHITECTURE.md` - Full architecture documentation
- `docs/I18N_API.md` - API reference
- `docs/I18N_TESTING_GUIDE.md` - Testing guide

---

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 20. Comment-Reply System

### Overview

ScriptLog includes a complete comment-reply system that allows threaded discussions on blog posts. Replies are stored in the same `tbl_comments` table using a self-referential `comment_parent_id` field.

### Architecture

```
Comments (comment_parent_id = 0)
    └── Reply 1 (comment_parent_id = parent_comment_id)
    └── Reply 2 (comment_parent_id = parent_comment_id)
```

### Database Schema

The reply system uses the existing `tbl_comments` table structure:

| Field | Type | Description |
|-------|------|-------------|
| `ID` | BIGINT | Primary key |
| `comment_post_id` | BIGINT | FK to tbl_posts |
| `comment_parent_id` | BIGINT | Parent comment ID (0 for top-level comments) |
| `comment_author_name` | VARCHAR(60) | Author's name |
| `comment_author_ip` | VARCHAR(100) | Author's IP address |
| `comment_author_email` | VARCHAR(100) | Author's email |
| `comment_content` | text | Comment/reply content |
| `comment_status` | VARCHAR(20) | Status: approved, pending, spam, draft |
| `comment_date` | datetime | Creation timestamp |

### Core Components

| Component | Location | Purpose |
|----------|----------|---------|
| `ReplyDao` | `lib/dao/ReplyDao.php` | Reply CRUD operations |
| `ReplyService` | `lib/service/ReplyService.php` | Business logic for replies |
| `ReplyController` | `lib/controller/ReplyController.php` | HTTP request handling |
| `CommentDao` | `lib/dao/CommentDao.php` | Comment operations (includes `countReplies()`) |
| `CommentService` | `lib/service/CommentService.php` | Comment business logic |

### Admin Panel Routing

| Action | URL | Description |
|--------|-----|-------------|
| **List Comments** | `?load=comments` | View all comments |
| **Edit Comment** | `?load=comments&action=editComment&Id={id}` | Edit a comment |
| **Reply to Comment** | `?load=reply&action=reply&Id={parent_id}` | Create new reply |
| **Edit Reply** | `?load=reply&action=editReply&Id={reply_id}` | Edit existing reply |
| **Delete Reply** | `?load=reply&action=deleteReply&Id={reply_id}` | Delete reply |
| **Delete Comment** | `?load=comments&action=deleteComment&Id={id}` | Delete comment (also deletes replies) |

### Whitelisting Routes

To add a new admin route, update `lib/utility/admin-query.php`:

```php
function admin_query()
{
    return array(
        // ... existing routes ...
        'comments' => 'comments.php',
        'reply' => 'reply.php',  // Add this line
        // ... other routes ...
    );
}
```

### Action Constants

Defined in `lib/core/ActionConst.php`:

```php
// Comment constants
const COMMENTS      = "comments";
const EDITCOMMENT   = "editComment";
const DELETECOMMENT = "deleteComment";

// Reply constants
const REPLY         = "reply";
const EDITREPLY     = "editReply";
const DELETEREPLY   = "deleteReply";
```

### Access Control

Reply functionality requires `ActionConst::REPLY` permission, available to:
- **administrator** - Full access
- **manager** - Full access
- **editor** - Full access
- **author** - Full access

### ReplyDao Methods

```php
class ReplyDao extends Dao
{
    // Create a new reply
    public function createReply($bind);
    
    // Find all replies for a parent comment
    public function findReplies($commentId, $orderBy = 'ID');
    
    // Find a single reply by ID
    public function findReply($id, $sanitize);
    
    // Update reply
    public function updateReply($sanitize, $bind, $ID);
    
    // Delete reply
    public function deleteReply($id, $sanitize);
    
    // Check if reply exists
    public function checkReplyId($id, $sanitize);
    
    // Get parent comment info
    public function getParentComment($parentId, $sanitize);
    
    // Count total replies
    public function totalReplyRecords($data = null, $parentId = null);
    
    // Generate status dropdown
    public function dropDownReplyStatement($selected = '');
}
```

### ReplyService Methods

```php
class ReplyService
{
    // Setters
    public function setReplyId($reply_id);
    public function setPostId($post_id);
    public function setParentId($parent_id);
    public function setAuthorName($author_name);
    public function setAuthorIP($author_ip);
    public function setAuthorEmail($author_email);
    public function setReplyContent($content);
    public function setReplyStatus($status);
    
    // Getters/Operations
    public function grabReplies($parentId, $orderBy = 'ID');
    public function grabReply($id);
    public function grabParentComment($parentId);
    public function addReply();
    public function modifyReply();
    public function removeReply();
    public function checkReplyExists($id);
    public function totalReplies($data = null, $parentId = null);
}
```

### ReplyController Methods

```php
class ReplyController extends BaseApp
{
    // List all replies for a comment
    public function listItems($parentId = null);
    
    // Create new reply (handles both GET for form and POST for submission)
    public function insert();
    
    // Update existing reply
    public function update($id);
    
    // Delete reply
    public function remove($id);
}
```

### Admin Page Implementation

#### admin/reply.php Routing

```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$replyId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;

$replyDao = new ReplyDao();
$replyService = new ReplyService($replyDao, $validator, $sanitizer);
$replyController = new ReplyController($replyService);

try {
    switch ($action) {
        case ActionConst::REPLY:
            // GET: show reply form, POST: process submission
            if ($authenticator->userAccessControl(ActionConst::REPLY)) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $replyController->insert();
                } else {
                    $replyController->insert();
                }
            }
            break;
            
        case ActionConst::EDITREPLY:
            // Edit existing reply
            if ($authenticator->userAccessControl(ActionConst::REPLY)) {
                $replyController->update($replyId);
            }
            break;
            
        case ActionConst::DELETEREPLY:
            // Delete reply
            if ($authenticator->userAccessControl(ActionConst::REPLY)) {
                $replyController->remove($replyId);
            }
            break;
    }
} catch (Throwable $th) {
    LogError::exceptionHandler($th);
}
```

### Frontend Comment Submission

Visitors can submit comments and replies via `comments-post.php`:

```php
// From public/themes/blog/single.php
<form method="post" action="<?= retrieve_site_url() ?>/comments-post.php">
    <input type="hidden" name="post_id" value="<?= $post_id ?>">
    <input type="hidden" name="parent_id" value="0"> <!-- 0 for comment, parent_id for reply -->
    <input type="text" name="name" placeholder="Name">
    <input type="email" name="email" placeholder="Email">
    <textarea name="comment" placeholder="Comment"></textarea>
    <button type="submit">Submit</button>
</form>
```

### Viewing Replies in Admin

#### Comments List (all-comments.php)

Shows reply counts per comment:

```php
$replyCount = $commentService->countReplies($comment['ID']);
if ($replyCount > 0) {
    echo '<span class="badge bg-blue">' . $replyCount . ' replies</span>';
}
```

#### Reply Form (reply.php)

Form for creating/editing replies:

```php
<form method="post" action="<?= generate_request('index.php', 'post', ['reply', $action, $reply_id])['link'] ?>">
    <input type="text" name="author_name" value="<?= htmlspecialchars($replyData['comment_author_name'] ?? '') ?>">
    <textarea name="reply_content"><?= htmlspecialchars($replyData['comment_content'] ?? '') ?></textarea>
    <?= $replyStatus // Dropdown for status ?>
    <input type="hidden" name="csrfToken" value="<?= csrf_generate_token('csrfToken') ?>">
    <button type="submit" name="replyFormSubmit">Submit Reply</button>
</form>
```

### Deleting Comments with Replies

When deleting a parent comment, consider whether to:
1. Delete all child replies (cascade delete)
2. Keep replies and reassign to a system account

Current implementation: Manual deletion required for each reply.

### Testing the Reply System

```bash
# Test comment listing with reply counts
curl http://example.com/admin/index.php?load=comments

# Test reply form display
curl http://example.com/admin/index.php?load=reply&action=reply&Id=5

# Test reply submission (requires authentication)
curl -X POST http://example.com/admin/index.php \
  -d "load=reply&action=reply&Id=5" \
  -d "author_name=Test&reply_content=Test+reply&reply_status=pending&replyFormSubmit=1"
```

---

## 21. Content Import System

### Overview

ScriptLog includes a robust content import system that supports migrating data from WordPress (WXR), Ghost (JSON), Blogspot/Blogger (XML), and ScriptLog's native JSON format. The native format allows migration between ScriptLog installations, preserving menus, settings, and content relationships.

### Architecture

The import system follows the project's standard layered pattern:

1.  **UI Layer**: `admin/ui/import/index.php` (upload form) and `preview.php` (data verification).
2.  **Controller Layer**: `ImportController` handles requests, CSRF validation, and user assignment.
3.  **Service Layer**: `MigrationService` coordinates the import process and handles database interactions via `dbc`.
4.  **Utility Layer**: Specific importer classes (`WordPressImporter`, `GhostImporter`, `BlogspotImporter`, `ScriptlogImporter`) handle file parsing.

### Core Components

| Component | Location | Purpose |
| :--- | :--- | :--- |
| `ImportController` | `lib/controller/ImportController.php` | Request handling and CSRF protection |
| `MigrationService` | `lib/service/MigrationService.php` | Main import logic and DB operations |
| `WordPressImporter` | `lib/utility/import-wordpress.php` | WXR (XML) parser |
| `GhostImporter` | `lib/utility/import-ghost.php` | Ghost JSON parser |
| `BlogspotImporter` | `lib/utility/import-blogspot.php` | Blogger XML parser |
| `ScriptlogImporter` | `lib/utility/import-scriptlog.php` | Native JSON parser |
| `ImportException` | `lib/core/ImportException.php` | Specialized import error handling |

### Import Workflow

1.  **Upload**: User selects source platform and uploads export file.
2.  **Preview**: `MigrationService::previewImport()` parses the file and returns a summary and sample data.
3.  **Import**:
    *   Categories are created or mapped if they already exist.
    *   Posts/Pages are created with unique slugs.
    *   Comments are imported and linked to their respective posts.
    *   Content is assigned to the selected author.

### Security and Validation

*   **CSRF Protection**: All import actions require a valid security token.
*   **Access Control**: Only users with `administrator` level can access the import feature.
*   **Sanitization**: Imported HTML is purified using `purify_dirty_html()` and input is sanitized via `prevent_injection()`.
*   **Duplicate Prevention**: Existing posts with the same slug are skipped or renamed to ensure uniqueness.

### Adding New Importers

To add support for a new platform:

1.  Create a new importer class in `lib/utility/` (e.g., `MediumImporter.php`).
2.  Run `php generate-utility-list.php` to register the new utility.
3.  Update `MigrationService.php` to include the new source constant and handle the new importer.
4. Update the UI in `admin/ui/import/index.php` to add the new option.

---

## 22. Content Export System

### Overview

ScriptLog includes a content export system that supports exporting data to WordPress (WXR), Ghost (JSON), Blogspot/Blogger (XML), and ScriptLog's native JSON format. The native format preserves menus, settings, and content relationships for seamless migration between installations.

### Architecture

The export system follows the project's standard layered pattern:

1.  **UI Layer**: `admin/ui/export/index.php` (format selection form).
2.  **Controller Layer**: `ExportController` handles requests and format selection.
3.  **Service Layer**: `ExportService` coordinates the export process and data retrieval.
4.  **Utility Layer**: Specific exporter classes (`WordPressExporter`, `GhostExporter`, `BlogspotExporter`, `ScriptlogExporter`) handle format generation.

### Core Components

| Component | Location | Purpose |
| :--- | :--- | :--- |
| `ExportController` | `lib/controller/ExportController.php` | Request handling |
| `ExportService` | `lib/service/ExportService.php` | Main export logic and data retrieval |
| `WordPressExporter` | `lib/utility/export-wordpress.php` | WXR (XML) generator |
| `GhostExporter` | `lib/utility/export-ghost.php` | Ghost JSON generator |
| `BlogspotExporter` | `lib/utility/export-blogspot.php` | Blogger XML generator |
| `ScriptlogExporter` | `lib/utility/export-scriptlog.php` | Native JSON generator |
| `ExportException` | `lib/core/ExportException.php` | Specialized export error handling |

### Export Workflow

1.  **Select Format**: User selects target platform (WordPress, Ghost, Blogspot, or Scriptlog).
2.  **Generate**: `ExportService` retrieves posts, pages, categories, tags, and comments from the database.
3.  **Transform**: Selected exporter formats the data according to target platform specifications.
4.  **Download**: File is generated and sent to browser for download.

### Native Scriptlog Format

The Scriptlog native export format (`export-scriptlog.php`) preserves:

- Posts, pages, categories, tags, and comments
- Navigation menus and menu items
- System settings and configuration
- Post-topic relationships
- Content metadata

This format is ideal for migrating between Scriptlog installations or creating backups.

### Security and Access Control

*   **Access Control**: Only users with `administrator` level can access the export feature.
*   **Admin Route Only**: Export is not exposed as a public route - it's accessed via `admin/index.php?load=export`.
*   **Whitelist**: Export is registered in `lib/utility/admin-query.php` for admin routing.

### Adding New Exporters

To add support for a new platform:

1.  Create a new exporter class in `lib/utility/` (e.g., `MediumExporter.php`).
2.  Implement format generation logic in the exporter class.
3.  Update `ExportController.php` to include the new format option.
4.  Update the UI in `admin/ui/export/index.php` to add the new option.

### Common Issues and Solutions

#### XML Parse Error: Unexpected Identifier

When generating XML files (WordPress WXR, Blogspot Atom), the XML declaration `<?xml version="1.0" encoding="UTF-8"?>` may cause a PHP parse error if placed inline with PHP code. This happens because PHP interprets `<?` as a short opening tag.

**The Problem:**
```php
// This causes parse error - PHP tries to interpret "xml" as PHP code
ob_start();
?>
<?xml version="1.0" encoding="UTF-8"?>
<rss ...
```

**The Solution:**
Use PHP string concatenation to output XML content:

```php
public function export(&$exportStats, $authorId = null)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<rss version="2.0">' . "\n";
    // ... build XML as string
    return $xml;
}
```

**Files Fixed:**
- `lib/utility/export-wordpress.php`
- `lib/utility/export-blogspot.php`

---

## 23. UI Asset Management

### Overview

ScriptLog manages UI assets (CSS, JavaScript, images) separately for the admin panel and the public theme. Understanding the asset structure is essential for theming and plugin development.

### Asset Locations

| Location | Purpose |
|----------|---------|
| `admin/assets/` | Admin panel CSS, JS, images |
| `public/themes/blog/assets/` | Blog theme CSS, JS, images |

### Active Admin Assets

**Admin Panel (admin/assets/):**
- `dist/css/AdminLTE.min.css` - Main admin theme
- `dist/css/skins/scriptlog-skin.css` - Active admin skin
- `dist/css/rtl.css` - RTL language support
- `components/bootstrap/dist/css/bootstrap.min.css`
- `components/font-awesome/css/font-awesome.min.css`

### Active Theme Assets

**Blog Theme (public/themes/blog/assets/):**
- `css/style.sea.min.css` - Main theme style (minified)
- `css/sina-nav.min.css` - Navigation styles (minified)
- `js/front.min.js` - Main theme logic (minified)
- `js/sina-nav.min.js` - Navigation logic (minified)
- `vendor/bootstrap/css/bootstrap.min.css`
- `vendor/font-awesome/css/font-awesome.min.css`

### Asset Optimization (Performance)

To maintain high performance (Target: 100/100 Lighthouse), follow these patterns:

#### 1. Minification
Always use minified versions of CSS and JS in production. A helper script `tmp/minify.php` can be used to generate `.min` versions of theme assets.

**Theme Asset Minification Script:**

| File | Purpose |
|------|---------|
| `tmp/minify.php` | Development utility to generate minified `.min.css` and `.min.js` files |

**Usage:**
```bash
php tmp/minify.php
```

**What it does:**
- Scans `public/themes/blog/assets/css/` for `.css` files (skips `.min.css`)
- Scans `public/themes/blog/assets/js/` for `.js` files (skips `.min.js`)
- Generates corresponding `.min.css` and `.min.js` versions
- Removes comments, whitespace, and redundant characters

**When to use:**
- After modifying source CSS/JS files before deployment
- During development when adding new non-minified assets
- Before committing to ensure production uses optimized files

**Workflow:**
```bash
# 1. Edit source files in public/themes/blog/assets/css/ or js/
# 2. Run minification
php tmp/minify.php

# 3. Verify minified versions were created
ls -la public/themes/blog/assets/css/*.min.css
ls -la public/themes/blog/assets/js/*.min.js
```

> **Note:** Minified versions are already committed to the repository. This script is for development workflow when adding or modifying theme assets.

#### 2. Critical CSS
Inline above-the-fold CSS in `header.php` to prevent render-blocking. Essential layout, navigation, and hero styles should be inlined within `<style>` tags.

#### 3. Asset Deferral
Use the `defer` attribute for all non-critical scripts in `footer.php`. This allows the browser to continue parsing HTML while scripts are being downloaded.

#### 4. Compression & Caching
Server-side compression (Gzip) and browser caching are configured in `.htaccess`. Ensure these rules are moved to the web server configuration (Nginx/Apache) for maximum efficiency.

### Performance Testing

To ensure optimizations are maintained, the project includes specific performance-related tests in the test suite.

#### 1. Page Cache Testing
Unit tests in `tests/unit/PageCacheTest.php` verify the full-page caching logic, ensuring that cache keys are generated correctly and that sensitive pages (search, logged-in sessions) are never cached.

#### 2. DAO Eager Loading
Integration tests in `tests/integration/PostDaoIntegrationTest.php` verify that the DAO layer uses efficient `INNER JOIN` queries and database indexes. This ensures minimal Time to First Byte (TTFB) by reducing the number of database round-trips.

#### 3. Running Performance Tests
Run the specific performance test suite using:
```bash
lib/vendor/bin/phpunit --bootstrap tests/bootstrap_integration.php --filter "PostDaoIntegration|PageCache"
```

### Asset Cleanup Guidelines

**Before deleting any asset files:**

1. **Read template files** that include assets:
   - `admin/admin-layout.php` - Admin header template
   - `public/themes/blog/header.php` - Theme header template
   - `public/themes/blog/footer.php` - Theme footer template

2. **Search for references** using grep:
   ```bash
   grep -r "asset-path" .
   grep -r "stylesheet\|script.*src" admin/ public/themes/
   ```

3. **Verify existence** before cleanup:
   ```bash
   ls -la path/to/asset.css
   ```

**Files that are safe to remove:**
- Non-minified `.css`/`.js` files when minified versions exist
- Duplicate libraries in different formats
- Reference documentation files (e.g., `icons-reference/`)
- License files in vendor directories
- SCSS/Less source files in vendor directories (not compiled)
- Non-minified theme CSS when `.min.css` versions are loaded

**Files to NEVER remove without verification:**
- Files referenced in layout templates
- Minified versions (they're typically what's used)
- Skin files actively used by the theme
- Development utilities (`tmp/minify.php`)

---

## 24. Dynamic SMTP System

### Overview

ScriptLog features a dynamic SMTP configuration system that allows administrators to manage email settings directly from the dashboard. This system replaces static configuration in `config.php` with database-driven settings, enabling real-time updates without manual file modification.

### Architecture

The SMTP system integrates with the project's multi-layered architecture:

1.  **UI Layer**: `admin/ui/setting/mail-setting.php` (configuration form).
2.  **Controller Layer**: `ConfigurationController::updateMailSetting()` handles request processing, CSRF validation, and data persistence.
3.  **Service Layer**: 
    *   `ConfigurationService` manages the underlying `tbl_settings` operations.
    *   `NotificationService` orchestrates email delivery using **Symfony Mailer**.
4.  **Data Layer**: `ConfigurationDao` interacts with `tbl_settings` using prepared statements.

### Core Components

| Component | Location | Purpose |
| :--- | :--- | :--- |
| `NotificationService` | `lib/service/NotificationService.php` | Main email delivery service with database fallback. |
| `ConfigurationController` | `lib/controller/ConfigurationController.php` | Handles SMTP setting updates in the admin panel. |
| `MAIL_CONFIG` | `lib/core/ActionConst.php` | Action constant for mail configuration. |
| `option-mail.php` | `admin/option-mail.php` | Admin entry point for mail settings. |

### Configuration Keys (tbl_settings)

The following keys are used in `tbl_settings` to store SMTP configuration:

*   `smtp_host`: SMTP server hostname (e.g., `smtp.gmail.com`).
*   `smtp_port`: SMTP server port (e.g., `587`, `465`).
*   `smtp_encryption`: Encryption method (`tls`, `ssl`, or `none`).
*   `smtp_username`: SMTP authentication username.
*   `smtp_password`: SMTP authentication password.
*   `smtp_from_email`: Default "From" email address.
*   `smtp_from_name`: Default "From" name (e.g., `Blogware`).

### Implementation Details

#### 1. Configuration Priority
`NotificationService` prioritizes settings found in the database. If a setting is missing or empty in `tbl_settings`, it gracefully falls back to the values defined in `config.php`.

#### 2. Security
*   **CSRF Protection**: All SMTP setting updates are protected by the project's built-in CSRF defender.
*   **Password Handling**: SMTP passwords are submitted via secure POST requests and stored in the database.
*   **Input Validation**: Ports are validated as numeric, and "From" addresses are validated as legitimate email formats.

### Usage Example

To send an email using the dynamic SMTP system:

```php
// The NotificationService automatically loads settings from the DB
$notification = new NotificationService($configService);
$notification->send('user@example.com', 'Subject', 'Email body');
```

---

## 25. Search Functionality

The blog includes a secure AJAX-based search functionality in the sidebar widget.

### Overview

The search system provides real-time search results as users type, with support for both posts and pages. Results are returned via a REST API endpoint and displayed in a dropdown below the search input.

### Key Files

| File | Purpose |
|------|---------|
| `lib/core/SearchFinder.php` | Core search class using Db (PDO wrapper) |
| `lib/controller/api/SearchApiController.php` | REST API controller for search |
| `public/themes/blog/sidebar.php` | Search form in sidebar |
| `public/themes/blog/assets/js/search.js` | AJAX search JavaScript |
| `public/themes/blog/assets/css/custom.css` | Search result dropdown styles |
| `api/index.php` | API route registration |

### Architecture

```
User types in search box
       |
       v
search.js (AJAX) --[keyword]--> SearchApiController
       |
       v
SearchFinder (searches DB using Db class)
       |
       v
JSON response with results
       |
       v
search.js (displays dropdown)
```

### Search API Endpoints

| Endpoint | Method | Description |
|---------|--------|-------------|
| `/api/v1/search` | GET | Search all content (posts + pages) |
| `/api/v1/search/posts` | GET | Search posts only |
| `/api/v1/search/pages` | GET | Search pages only |

### Search API Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | Yes | Search keyword (min 2, max 100 chars) |
| `type` | string | No | `all`, `posts`, or `pages` (default: `all`) |

### Example Request

```
GET /api/v1/search?q=cicero&type=all
```

### Example Response

```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "title": "Lorem ipsum dolor sit amet",
      "slug": "lorem-ipsum",
      "excerpt": "Lorem ipsum dolor sit amet, consectetur...",
      "type": "post",
      "date": "2026-03-01",
      "url": "/post/1/lorem-ipsum"
    }
  ]
}
```

### Security Features

| Feature | Implementation |
|---------|---------------|
| **XSS Prevention** | Server-side sanitization via `sanitizeKeyword()` function |
| **SQL Injection** | Uses prepared statements via Db class (PDO wrapper) |
| **CSRF Protection** | Hidden CSRF token in search form, validated on submit |
| **Input Validation** | Keyword length limits (min 2, max 100 characters) |

### URL Format Support

The search results support both SEO-friendly and query string URLs based on permalink settings:

**SEO-Friendly URLs (when permalinks enabled):**
- Posts: `/post/ID/slug`
- Pages: `/page/slug`

**Query String URLs (when permalinks disabled):**
- Posts: `?p=ID`
- Pages: `?pg=ID`

### Implementation Notes

- The SearchFinder class uses the custom `Db` class (PDO wrapper), NOT Medoo
- Database connection accessed via `Registry::get('dbc')`
- API routes registered in `api/index.php`
- Public endpoint (no authentication required)
- Results include: id, title, slug, excerpt, type, date, url
- The search uses FULLTEXT index on `tbl_posts` (post_tags, post_title, post_content)

### Adding Search to Custom Themes

To add search to a custom theme:

1. Include the search form in your template:
```php
<form id="search-form" method="get" action="">
    <input type="hidden" name="csrf_token" value="<?php echo block_csrf(); ?>">
    <input type="text" id="search-keyword" name="q" placeholder="Search..." autocomplete="off">
    <div id="search-results" class="search-results-dropdown"></div>
</form>
```

2. Include the search JavaScript in your footer:
```php
<script src="<?php echo app_url(); ?>/themes/your-theme/assets/js/search.js"></script>
```

3. Add CSS styles for the search dropdown (see `custom.css` for reference).

---

## 26. Premium UI Standards

### Overview

Scriptlog follows a specific design language for system interfaces (Installer, Admin Tools) and high-end frontend pages (e.g., Privacy Policy). This is known as the **Minimalist & Elegant Dashboard Pattern**.

### Core Principles

| Principle | Implementation |
|-----------|----------------|
| **Color Palette** | High-contrast **Navy Dark Blue (#000080)** and **Chartreuse (#7FFF00)**. |
| **Typography** | Primary font: **'Outfit'** (Google Fonts). Use variable weights (300 to 800). |
| **Glassmorphism** | Translucent cards with `backdrop-filter: blur(25px)` for depth. |
| **Motion** | Subtle `fadeInUp` animations for entrance and hover state transitions. |
| **Focus** | Single-column centered layouts for long-form content to maximize readability. |

### Implementation Example (Frontend)

When applying this pattern to a frontend page (like `privacy.php`), follow these structural rules:

1.  **Dedicated Stylesheet**: Create a page-specific CSS file (e.g., `assets/css/privacy.css`) to avoid bloat in `style.sea.css`.
2.  **Hero Section**: Use a gradient background (Navy) with Chartreuse accents for the page header.
3.  **Glass Card**: Wrap the main content in a container with glassmorphism effects.
4.  **Semantic Icons**: Enhance headings with FontAwesome icons.

#### CSS Pattern

```css
.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(25px);
    border: 1px solid rgba(0, 0, 128, 0.1);
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 128, 0.15);
}

.animate-up {
    animation: fadeInUp 0.8s ease forwards;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
```

### Best Practices

*   **Preload Fonts**: Ensure 'Outfit' is preloaded in `header.php` to prevent FOUT (Flash of Unstyled Text).
*   **Keep Logic Separate**: Do not mix premium UI markup with complex PHP logic; keep templates clean.
*   **Mobile-First**: Test all glassmorphism effects on mobile; ensure borders and shadows don't create visual clutter on small screens.

---

## 27. Password-Protected Posts

### Overview

ScriptLog includes a secure password-protected posts system that allows users to lock post content with a password. The system uses AJAX for unlock functionality without page reload.

### Security Features

| Feature | Implementation |
|---------|---------------|
| **Database-only storage** | All password hashes stored in database, no credential files |
| **Bcrypt hashing** | Passwords verified against bcrypt hash |
| **AES-256-CBC encryption** | Post content encrypted with unique passphrase |
| **Rate limiting** | Max 5 failed attempts per 15 minutes per post/IP |
| **XSS protection** | Content sanitized with htmLawed after decryption |
| **Inline style stripping** | Removes Word paste formatting artifacts |

### Architecture

```
Frontend User Flow:
1. User visits protected post → sees password form (no content)
2. User enters password → AJAX request to API
3. API verifies password (bcrypt hash match)
4. If valid: API decrypts content (using passphrase) and returns it
5. Frontend replaces form with decrypted content

Admin Flow:
1. Admin edits protected post → content auto-decrypted for editing
2. Admin saves → content re-encrypted with new passphrase
```

### Database Schema

**tbl_posts columns used for protection:**

| Column | Purpose |
|--------|---------|
| `post_visibility` | Set to `protected` for protected posts |
| `post_password` | Bcrypt hash of the password |
| `passphrase` | MD5 hash used for encryption: `md5(app_key + password)` |
| `post_content` | AES-encrypted content |

### Key Files

| File | Purpose |
|------|---------|
| `lib/controller/api/ProtectedPostApiController.php` | API controller with unlock/verify endpoints |
| `lib/utility/protected-post.php` | `decrypt_post()`, `decrypt_post_admin()`, rate limiting functions |
| `lib/utility/encrypt-decrypt.php` | `encrypt()`, `decrypt()` using AES-256-CBC |
| `lib/core/FrontHelper.php` | `grabPreparedFrontPostById()` - includes protected posts |
| `public/themes/blog/assets/js/unlock-post.js` | AJAX form handler |
| `public/themes/blog/single.php` | Uses AJAX unlock for protected posts |
| `admin/ui/posts/edit-post.php` | Decrypts content for admin editing |
| `api/index.php` | Routes: POST `/api/v1/posts/{id}/verify`, POST `/api/v1/posts/{id}/unlock` |

### API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/v1/posts/{id}/verify` | POST | None | Verify password (returns success/fail only) |
| `/api/v1/posts/{id}/unlock` | POST | None | Verify password AND return decrypted content |

### Request/Response Examples

**Unlock Request:**
```json
POST /api/v1/posts/3/unlock
{
  "password": "Bac4D0nG(*)#"
}
```

**Unlock Response (success):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "content": "<p>Decrypted post content here...</p>"
  }
}
```

**Unlock Response (rate limited):**
```json
{
  "success": false,
  "status": 429,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many failed attempts. Please try again later."
  }
}
```

### Rate Limiting Functions

| Function | Purpose |
|----------|---------|
| `is_unlock_rate_limited($postId)` | Check if IP has exceeded max attempts |
| `track_failed_unlock_attempt($postId)` | Record failed attempt (clears after 15 min) |
| `clear_failed_unlock_attempts($postId)` | Clear attempts after successful unlock |
| `get_failed_unlock_attempts($postId)` | Get current attempt count |

### Unit Tests

**Total: 59 tests across 3 files**

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `tests/unit/ProtectedPostTest.php` | 12 | Core encryption/decryption functions |
| `tests/unit/ProtectedPostRateLimitTest.php` | 20 | Rate limiting & password strength |
| `tests/unit/PostControllerProtectedPostTest.php` | 27 | Controller flow & validation |

Run tests:
```bash
php lib/vendor/phpunit/phpunit/phpunit tests/unit/ProtectedPost*.php --bootstrap tests/bootstrap.php
```

---

## 28. Summernote AJAX Image Upload

### Overview

Summernote WYSIWYG editor includes AJAX image upload functionality for inserting images into post/page content. The upload system uses a direct admin endpoint with proper authentication.

### Implementation Details

| Component | Location | Purpose |
|-----------|----------|---------|
| Upload Endpoint | `admin/media-upload.php` | Direct upload handler with session auth |
| AJAX Handler | `admin/admin-layout.php` | jQuery AJAX configuration |
| Media DAO | `lib/dao/MediaDao.php` | Database storage for media metadata |
| Upload Utility | `lib/utility/upload-photo.php` | Image processing (resize + WebP) |

### Authentication Flow

The upload uses admin session authentication instead of API authentication:

```
1. Admin opens post editor (Summernote initialized)
2. Admin clicks image button in toolbar
3. Admin selects image file
4. AJAX sends POST to /admin/media-upload.php
5. Endpoint validates session via Session::getInstance()
6. If valid: process upload, save to database, return JSON URL
7. If invalid: return 401 Unauthorized
```

### Root Causes of Original Issues

The initial implementation had three issues that prevented uploads:

| Issue | Root Cause | Solution |
|-------|-----------|---------|
| "Unauthorized" error | Cookie path was `/admin/` instead of `/` | Changed `COOKIE_PATH` in `Authentication.php` |
| Session not initialized | API entry point didn't initialize sessions | Used direct admin endpoint |
| JSON parse error | Output buffering issues | Clean output buffers before response |

### Key Files Modified

| File | Change |
|------|--------|
| `lib/core/Authentication.php` | Changed `COOKIE_PATH` from `APP_ADMIN` to `/` |
| `admin/media-upload.php` | New - direct upload endpoint with session auth |
| `admin/admin-layout.php` | Updated AJAX URL and `withCredentials` setting |

### admin/media-upload.php

Created a dedicated upload endpoint with:

- **Session Authentication**: Uses `Session::getInstance()` (shares admin session context)
- **Output Buffering**: Cleans all output buffers before JSON response
- **Error Suppression**: `error_reporting(0)` prevents PHP errors in JSON output
- **Database Storage**: Saves to `tbl_media` and `tbl_mediameta`
- **Image Processing**: Creates 3 sizes + WebP via `upload_photo()`

```php
<?php
// Key features of the endpoint:

// 1. Disable output and errors
error_reporting(0);
ini_set('display_errors', 0);

// 2. Clean all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// 3. Start fresh buffer
ob_start();

// 4. Session authentication
$session = Session::getInstance();
if (!$session->get('scriptlog_session_login')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 5. Process upload
ob_start();
upload_photo(...);
$result = ob_get_clean();

// 6. Save to database
$mediaId = $mediaDao->insertMedia($data);
$mediaDao->insertMediaMeta($mediaId, 'post_id', $postId);

// 7. Return clean JSON
echo json_encode([
    'success' => true,
    'url' => $imageUrl,
    'filename' => $filename,
    'media_id' => $mediaId
]);
```

### AJAX Configuration (admin-layout.php)

```javascript
$.ajax({
    url: '/admin/media-upload.php',  // Direct admin endpoint
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    xhrFields: {
        withCredentials: true  // Send cookies with request
    },
    success: function(response) {
        // Insert image into editor
        summernote.summernote('insertImage', response.url);
    },
    error: function(xhr) {
        // Show error message
        alert('Failed to upload image: ' + xhr.statusText);
    }
});
```

### Database Schema

**tbl_media** - Image metadata:
```sql
- media_filename: Unique filename
- media_type: 'image'
- media_target: 'blog'
- media_user: Username who uploaded
```

**tbl_mediameta** - Post linkage:
```sql
- media_id: Links to tbl_media
- meta_key: 'post_id'
- meta_value: Post ID
```

### Response Format

**Success (201 Created):**
```json
{
  "success": true,
  "status": 201,
  "data": {
    "url": "/public/files/pictures/abc123_image.jpg",
    "filename": "abc123_image.jpg",
    "media_id": 42,
    "post_id": 5
  }
}
```

**Error (401 Unauthorized):**
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

### Testing

1. Log out and log back in (to get new cookie with path `/`)
2. Go to Posts → Add New
3. Click image button in Summernote toolbar
4. Select image file
5. Verify:
   - Files created: `public/files/pictures/` has 4 versions + WebP
   - Database: `tbl_media` and `tbl_mediameta` have new records
   - Editor: Image inserted into content

### Commits

| Commit | Description |
|--------|-------------|
| `f2e1d91` | Fix Summernote AJAX image upload authentication |
| `5db174e` | Fix cookie path for AJAX API requests |
| `a593f39` | Use direct admin endpoint for Summernote image upload |
| `44db5e7` | Fix JSON response in media upload handler |

---

## Important: Respect Existing Code

This codebase belongs to the project owner/developer. As a developer working on this project, you must follow these rules:

### Rules for Modifying Existing Code

1. **NEVER rewrite existing working code** without explicit permission from the owner
2. **ALWAYS propose changes first** before modifying any existing file:
   - Explain what you want to change
   - Show the proposed solution
   - Wait for approval before implementing
3. **NEVER remove or change constants, functions, or logic flow** that the owner created
4. **If something works, DON'T fix it** - even if you think your approach is "better"
5. **Ask before changing** - When in doubt, always ask for permission

### Why This Matters

The owner has specific reasons for their code structure:
- Using `APP_IMAGE` constants instead of hardcoded paths
- Specific function signatures and return values
- Particular logic flow for business rules

### Example of what NOT to do:
```
❌ WRONG: "I'm going to rewrite invoke-webp-image.php to use hardcoded paths"
❌ WRONG: "I'll replace APP_IMAGE constants with direct strings"
❌ WRONG: "Let me change the function logic to my approach"
```

### Example of what to do:
```
✅ CORRECT: "I noticed an issue with image display. Can I propose a fix using the existing APP_IMAGE constants?"
✅ CORRECT: "Would you like me to enhance invoke-responsive-image.php while keeping APP_IMAGE constants?"
✅ CORRECT: "Can I add a new function next to the existing ones?"
```

### Lesson: What Happens When You Don't Ask

A developer once noticed images on the homepage had empty `src` attributes. Instead of asking the owner, they:
1. Replaced APP_IMAGE constants with hardcoded paths
2. Changed the function logic to their approach
3. Didn't test properly before claiming it worked
4. Broke the owner's existing working code

**The actual fix was simple:** The issue was `esc_attr()` being used in theme files (WordPress function). The developer should have checked the theme files first instead of modifying working utility code.

**What should have happened:**
1. Identify the issue (empty src attributes)
2. Propose a fix to the owner
3. Wait for approval
4. If approved, implement using existing constants/logic
5. Test on live site
6. Commit only after verification

---

## License

This project is licensed under the MIT License.

---

*Last Updated: April 2026 | Version 1.1.1*
