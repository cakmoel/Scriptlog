# Developer Guide - Scriptlog

**Version:** 1.0.0 | **Last Updated:** March 2026

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

> **TIP:** On Linux/Mac, ensure the web server user has write permissions to `public/cache/` and `public/log/`

### Running the Application

| Environment | URL |
|-------------|-----|
| **Public Site** | `http://your-domain/` |
| **Admin Panel** | `http://your-domain/admin/` |
| **API Endpoint** | `http://your-domain/api/v1/` |

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
- Saves the key to `lib/utility/.lts/lts.txt`
- Stores the key path in `config.php` under `app.defuse_key`
- This key is used for authentication cookie encryption

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

### Test Suite Overview

| Metric | Value |
|--------|-------|
| **Total Tests** | 790 |
| **Assertions** | ~900+ |
| **PHPUnit Version** | 9.6.34 |
| **Target Coverage** | 40% |

### Test Progress

| Phase | Status | Tests |
|-------|--------|-------|
| Phase 1: DAO Integration | ✅ Complete | 92 |
| Phase 2: Service Layer | ✅ Complete | 148 |
| Phase 3: Core Classes | 🔄 Pending | 65 |
| Phase 4: Controllers | 🔄 Pending | 34 |
| Phase 5: Utilities | 🔄 Pending | 26 |

### Running Tests

```bash
# Run all tests
lib/vendor/bin/phpunit

# Run with coverage (requires Xdebug)
lib/vendor/bin/phpunit --coverage-html coverage

# Run specific test file
lib/vendor/bin/phpunit tests/service/PostServiceTest.php

# Run tests matching pattern
lib/vendor/bin/phpunit --filter "Service"

# Run service tests only
lib/vendor/bin/phpunit tests/service/
```

### Static Analysis with PHPStan

This project uses PHPStan for static code analysis to find bugs without running the code.

```bash
# Run static analysis
vendor/bin/phpstan analyse

# Run with specific config
vendor/bin/phpstan analyse --configuration=phpstan.neon

# Generate baseline (captures existing issues)
vendor/bin/phpstan analyse --generate-baseline=phpstan.baseline.neon
```

#### PHPStan Configuration

The configuration is in `phpstan.neon`:

```neon
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

- **phpVersion**: Set to `70400` for PHP 7.4 compatibility
- **level**: Currently at level 0. Increase gradually for stricter type checking
- **Baseline**: Use `phpstan.baseline.neon` to track known issues

> **TIP:** Run PHPStan before committing to catch type errors early. For detailed testing guide, see [TESTING_GUIDE.md](TESTING_GUIDE.md).

### Test Categories

| Category | Description |
|----------|-------------|
| **Unit Tests** | Utility function tests, class existence tests |
| **Integration Tests** | Database CRUD operations using `blogware_test` database |
| **Service Tests** | Business logic tests with mocked DAOs (148 tests) |

#### Service Tests Coverage

| Service | Tests |
|---------|-------|
| CommentService | 10 |
| ConfigurationService | 10 |
| MediaService | 16 |
| MenuService | 14 |
| NotificationService | 14 |
| PageService | 16 |
| PluginService | 13 |
| PostService | 24 |
| ThemeService | 10 |
| TopicService | 7 |
| UserService | 18 |

#### Writing Tests

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

### Test Database Setup

```bash
# Create test database
php tests/setup_test_db.php
```

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

Admin panel uses a separate translation system via `lib/utility/admin-translations.php`:

```php
// Usage in admin views
admin_translate('dashboard');      // "Dashboard"
admin_translate('allLanguages');   // "All Languages"
admin_translate('addLanguage');    // "Add Language"
```

The function uses a static array for performance and scope safety.

### Populating Languages and Translations

Languages and translations are automatically populated during installation. No manual steps required.

This creates:
- 7 languages (en, ar, zh, fr, ru, es, id)
- 203 translations across 6 contexts

Use the admin panel (Settings → Languages) to manage translations.

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
- `css/style.sea.css` - Main theme style
- `css/sina-nav.css` - Navigation styles
- `vendor/@fancyapps/fancybox/jquery.fancybox.min.css` - Lightbox
- `vendor/bootstrap/css/bootstrap.min.css`
- `vendor/font-awesome/css/font-awesome.min.css`

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

**Files to NEVER remove without verification:**
- Files referenced in layout templates
- Minified versions (they're typically what's used)
- Skin files actively used by the theme

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

## License

This project is licensed under the MIT License.

---

*Last Updated: March 2026 | Version 1.0.1*
