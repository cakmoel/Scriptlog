# Developer Guide - Scriptlog

**Version:** 1.8.1 | **Last Updated:** August 2026

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
29. [Key Constants](#key-constants)
30. [Key Classes](#key-classes)
31. [Global Functions](#global-functions)
32. [Dependencies](#dependencies)
33. [Contributing](#contributing)
34. [License](#license)

> **NOTE:** For comprehensive testing documentation including PHPStan setup and CI/CD integration, see [TESTING_GUIDE.md](TESTING_GUIDE.md).

---

## 1. Getting Started

### Prerequisites

| Requirement | Version | Purpose |
|-------------|---------|---------|
| **PHP** | 7.4 - 8.5 | Server-side runtime |
| **MySQL/MariaDB** | 5.7+ | Database server |
| **Apache/Nginx** | Latest | Web server |
| **Composer** | Latest | Dependency management |

> **NOTE:** PHP extensions required: `pdo`, `pdo_mysql`, `json`, `mbstring`, `curl`, `gd`, `fileinfo`, `openssl`

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
|  7. Configuration saved to config.php and .env                |
+---------------------------------------------------------------+
```

**Step-by-Step Installation**

### Option 1: Clone from GitHub

```bash
# Clone repository
git clone https://github.com/cakmoel/Scriptlog.git
cd scriptlog

# Install dependencies
composer install

# Set permissions
chmod -R 755 public/
chmod -R 755 public/cache/ public/log/
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
# Front controller is index.php at the project root
```

### Running the Application

```bash
# From project root
cd /path/to/your-project
php -S localhost:8080
```

Then access the application at: **http://localhost:8080**

> **NOTE:** The front controller is `index.php` at the project root, so run the built-in server from the project root (no `-t` flag needed). With the wrong document root the server cannot locate `index.php` and returns a "Failed to open stream" error.

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

### 1.1 Configuration System

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
        'defuse_key' => '/var/www/your-project/storage/keys/[random_filename].php'
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

    'api' => [
        'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? ''
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

# --- API / CORS ---
CORS_ALLOWED_ORIGINS=
```

### Automatic Defuse Key Generation

During first-time installation, the system automatically:
- Generates a Defuse encryption key using `Defuse\Crypto\Key::createNewRandomKey()`
- **Saves the key outside the document root** for maximum security
- Generates a random filename (16 alphanumeric characters) for the key file
- Falls back to `lib/utility/.lts/` if storage directory is not writable
- Stores the key path in:
  - `config.php` under `app.defuse_key` (absolute path)
  - `.env` under `DEFUSE_KEY_PATH`
  - Database `tbl_settings` with key `defuse_key_path`
- This key is used for authentication cookie encryption

#### Key Location Logic

The `generate_defuse_key()` function in `install/include/setup.php` determines the key location as follows:

```
Primary Location (outside web root - RECOMMENDED):
/var/www/your-project/storage/keys/[random_filename].php

Fallback Location (inside web root - less secure):
/var/www/your-project/public_html/lib/utility/.lts/[random_filename].php
```

The function works as follows:

```php
// install/include/setup.php - generate_defuse_key() function
function generate_defuse_key()
{
    $appRoot = dirname(__DIR__, 2);       // e.g., /var/www/myblog/public_html
    $parentDir = dirname($appRoot);       // e.g., /var/www/myblog
    
    // Try to create storage directory outside web root
    $secureStorage = $parentDir . '/storage';
    $keyDir = $secureStorage . '/keys';
    
    if (!is_dir($keyDir)) {
        @mkdir($keyDir, 0755, true);
    }
    
    // Fallback to inside web root if not writable
    if (!is_dir($keyDir) || !is_writable($keyDir)) {
        $keyDir = $appRoot . '/lib/utility/.lts';
        if (!is_dir($keyDir)) {
            @mkdir($keyDir, 0755, true);
        }
    }
    
    // Add .htaccess protection in fallback location
    if (strpos($keyDir, $appRoot) !== false && !file_exists($keyDir . '/.htaccess')) {
        $htaccessContent = "# Deny all public access to encryption keys\nOrder deny,allow\nDeny from all\n";
        @file_put_contents($keyDir . '/.htaccess', $htaccessContent);
    }
    
    // Generate random filename and save key
    $keyFilename = generate_random_key_filename();
    $keyFile = $keyDir . '/' . $keyFilename;
    
    $key = Defuse\Crypto\Key::createNewRandomKey();
    $keyAscii = $key->saveToAsciiSafeString();
    
    $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\nreturn '$keyAscii';";
    file_put_contents($keyFile, $phpContent, LOCK_EX);
    
    return $keyFile;
}
```

#### Ensuring Keys Are Stored Outside Web Root

**Before running the installation**, you must create the storage directory:

```bash
# Navigate to your project parent directory
cd /var/www/myblog

# Create storage directory (sibling to public_html)
sudo mkdir -p storage/keys

# Set ownership to web server user
sudo chown -R www-data:www-data storage

# Set appropriate permissions
sudo chmod -R 755 storage
```

| Scenario | Storage Directory Created? | Key Location |
|----------|---------------------------|--------------|
| **Yes** (you created storage/keys/) | `/var/www/myblog/storage/keys/` | Outside web root - RECOMMENDED |
| **No** (skipped step above) | Not available | Falls back to `lib/utility/.lts/` (less secure) |

#### Security Note

- **Outside web root** (`storage/keys/`): Recommended - the key file cannot be accessed via HTTP
- **Inside web root** (`lib/utility/.lts/`): Less secure but protected by `.htaccess` (auto-generated)

If the key ends up in the fallback location, you can manually move it to `storage/keys/` after installation and update the path in `config.php`, `tbl_settings`, and `.env`.

### Key Files

| File | Location | Purpose |
|------|----------|---------|
| `config.php` | Root | Main configuration with `$_ENV` fallbacks |
| `.env` | Root | Environment variables (auto-generated) |
| `defuse_key` | `/var/www/your-project/storage/keys/[random_filename].php` | Encryption key for authentication |

> **Installation troubleshooting**: See `dev-docs/TROUBLESHOOTING.md` - [Installation Issues](TROUBLESHOOTING.md#installation-issues) and [Database Issues](TROUBLESHOOTING.md#database-issues).

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

### PSR-4 Autoloading & Namespace Convention

All project classes now use `Scriptlog\*` namespaces with backward-compatible `class_alias()` aliases:

| Directory | Namespace | Example |
|-----------|-----------|---------|
| `lib/core/` | `Scriptlog\Core` | `Scriptlog\Core\Bootstrap` |
| `lib/dao/` | `Scriptlog\Dao` | `Scriptlog\Dao\PostDao` |
| `lib/service/` | `Scriptlog\Service` | `Scriptlog\Service\PostService` |
| `lib/controller/` | `Scriptlog\Controller` | `Scriptlog\Controller\PostController` |
| `lib/controller/api/` | `Scriptlog\Controller\Api` | `Scriptlog\Controller\Api\PostsApiController` |
| `lib/model/` | `Scriptlog\Model` | `Scriptlog\Model\PostModel` |
| `lib/handler/` | `Scriptlog\Handler` | `Scriptlog\Handler\PostHandler` |

**Backward Compatibility:**
- `lib/autoload-aliases.php` - 232 `class_alias()` entries mapping old global names to new namespaced classes (kept as reference)
- `lib/autoload-aliases-map.php` - Static array map (232 entries) used by the lazy autoloader (fast, no class loading)
- `lib/main.php` and `tests/bootstrap.php` register a lazy `spl_autoload_register()` that creates aliases on demand - **only when old class names are actually used at runtime**

**Performance impact:** Lazy loading reduced autoload overhead from ~100ms to ~30ms and memory from 12MB to 6MB per request. See `benchmark/autoload_perf_bench.md`.

### Migration Script (`lib/migrate-namespaces.php`)

#### What Problem Did This Script Solve?

Before the migration, the 179 original project classes were in the **global namespace** - no `namespace` declarations at all. This meant any class could be referenced simply by its short name (e.g., `Bootstrap`, `PostDao`). While simple, this approach caused problems:

- **Collisions**: Two Composer packages could define the same class name
- **No autoloading control**: Composer's PSR-4 autoloader couldn't map paths to classes without namespaces
- **Hard to modernize**: Modern PHP frameworks and tools expect namespaced code

This script performed the **one-time migration** to add `namespace Scriptlog\*` declarations to all 179 original class files, add cross-namespace `use` imports where needed, and generate backward-compatible `class_alias()` entries so existing code continued working. Since then the project has grown to 232 classes, each new one being added manually to both alias files (see the TIP below).

#### How It Works (3-Step Algorithm)

| Step | What It Does | Files Affected |
|------|-------------|----------------|
| **1: Build class map** | Scans all `lib/` subdirectories, finds every class/interface/trait, maps each short name to its target namespace | None (read-only) |
| **2: Process each file** | For every PHP file: tokenizes it, preserves existing `use` statements, detects cross-namespace references (extends, implements, new, instanceof, catch, ::), injects `namespace` declaration + new `use` statements | All 179 class files in `lib/core/`, `lib/dao/`, `lib/service/`, `lib/controller/`, `lib/model/`, `lib/handler/` |
| **3: Generate aliases** | Writes `lib/autoload-aliases.php` with `class_alias()` entries mapping old global names (e.g., `Bootstrap`) to new namespaced names (e.g., `Scriptlog\Core\Bootstrap`) | `lib/autoload-aliases.php` (created; now 232 entries) |

#### What It Did NOT Do

- It did **not** create `lib/autoload-aliases-map.php` - that file was hand-crafted later in commit `20c376a2` as part of a performance optimization
- It did **not** update `lib/main.php` or `tests/bootstrap.php` - those were updated in a later commit to use the lazy autoloader
- It did **not** update `composer.json` - the PSR-4 autoloading config was added in Phase 1
- It did **not** delete any files - backup `.bak` files were cleaned up in a separate commit

#### The Tokenizer Logic (For Advanced Readers)

The script uses PHP's built-in tokenizer (`token_get_all()`) rather than regex to safely parse PHP files. Here is what each token analysis does:

```php
// 1. Find existing "use" statements by locating T_USE tokens
//    (must distinguish top-level use from trait use statements)
if ($tokens[$i][0] === T_USE) { ... }

// 2. Find the class/interface/trait declaration position
//    (everything before it is the "header" to be rewritten)
if ($tokens[$i][0] === T_CLASS && $classTokenPos === null) { ... }

// 3. Detect cross-namespace references by examining context
//    around each T_STRING token:
//    extends, implements, new, instanceof, catch → needs use import
//    :: (static call) → needs use import
//    T_STRING followed by variable → type hint, needs use import
if ($prevKind === T_EXTENDS || $prevKind === T_IMPLEMENTS || ...) {
    $shouldUse = true;
}
```

#### Practical Guidance for Developers Today

Since the migration script was **deleted** after use, adding new classes today is a **manual process**:

| When you create a new class... | You must update |
|------------------------------|----------------|
| New file in `lib/core/`, `lib/dao/`, etc. | Add the class to its appropriate namespace - no alias needed (new code uses namespaces) |
| New **public API** class referencing old global names | Add `class_alias()` entry to `lib/autoload-aliases.php` |
| New **public API** class referencing old global names | Add array entry to `lib/autoload-aliases-map.php` |

**Example - adding a new class manually:**

```php
<?php
// File: lib/service/NewsletterService.php
namespace Scriptlog\Service;

use Scriptlog\Dao\UserDao;  // Cross-namespace import (required!)

class NewsletterService
{
    // ...
}
```

Then add to both alias files:

```php
// lib/autoload-aliases.php
class_alias('Scriptlog\Service\NewsletterService', 'NewsletterService');

// lib/autoload-aliases-map.php (inside $aliasMap array)
'NewsletterService' => 'Scriptlog\Service\NewsletterService',
```

#### Full Source Code (Archive Reference)

The script below is the exact code committed in `99e7964c`. It is kept here for historical reference - **do not run it again** (it would add duplicate namespace declarations):

```php
<?php
/**
 * PSR-4 Phase 2 Migration Script
 *
 * 1. Adds namespace declarations to all project class files
 * 2. Adds use statements for cross-namespace references
 * 3. Preserves existing third-party use statements
 * 4. Generates lib/autoload-aliases.php for backward compatibility
 *
 * Usage: php lib/migrate-namespaces.php
 */

$dirs = [
    'Scriptlog\\Core'           => __DIR__ . '/core',
    'Scriptlog\\Dao'            => __DIR__ . '/dao',
    'Scriptlog\\Service'        => __DIR__ . '/service',
    'Scriptlog\\Controller'     => __DIR__ . '/controller',
    'Scriptlog\\Controller\\Api' => __DIR__ . '/controller/api',
    'Scriptlog\\Model'          => __DIR__ . '/model',
    'Scriptlog\\Handler'        => __DIR__ . '/handler',
];

$skipFiles = ['HTMLPurifier', 'Psr4AutoloadTest'];

$classMap = [];
$reverseMap = [];

foreach ($dirs as $ns => $dir) {
    $files = new DirectoryIterator($dir);
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;
        $baseName = $file->getBasename('.php');
        $skip = false;
        foreach ($skipFiles as $pattern) {
            if (strpos($baseName, $pattern) !== false) { $skip = true; break; }
        }
        if ($skip) continue;
        $content = file_get_contents($file->getPathname());
        if (preg_match('/^(?:abstract\s+)?(?:final\s+)?(?:class|interface|trait)\s+(\w+)/m', $content, $m)) {
            $className = $m[1];
            $classMap[$className] = $ns;
            $reverseMap[$ns][] = $className;
        }
    }
}
echo "Found " . count($classMap) . " project classes\n";

$processedFiles = 0;
$filesWithUses = 0;
$aliases = [];

foreach ($dirs as $targetNs => $dir) {
    $files = new DirectoryIterator($dir);
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;
        $baseName = $file->getBasename('.php');
        $skip = false;
        foreach ($skipFiles as $pattern) {
            if (strpos($baseName, $pattern) !== false) { $skip = true; break; }
        }
        if ($skip) continue;

        $path = $file->getPathname();
        $content = file_get_contents($path);

        $definedClasses = [];
        preg_match_all('/^(?:abstract\s+)?(?:final\s+)?(?:class|interface|trait)\s+(\w+)/m', $content, $matches);
        foreach ($matches[1] as $cName) {
            $definedClasses[] = $cName;
            $aliases[$cName] = $targetNs . '\\' . $cName;
        }
        if (empty($definedClasses)) { echo "  SKIP (no class): $path\n"; continue; }

        $tokens = @token_get_all($content);
        if (!$tokens) { echo "  ERROR tokenizing: $path\n"; continue; }

        $existingUses = [];
        $classTokenPos = null;

        for ($i = 0; $i < count($tokens); $i++) {
            if (!is_array($tokens[$i])) continue;

            if ($tokens[$i][0] === T_USE) {
                $useStmt = '';
                $j = $i;
                while ($j < count($tokens)) {
                    if (!is_array($tokens[$j])) {
                        $useStmt .= $tokens[$j];
                        if ($tokens[$j] === ';') break;
                    } else {
                        $useStmt .= $tokens[$j][1];
                        if ($tokens[$j][1] === ';') break;
                    }
                    $j++;
                }
                if (preg_match('/^use\s+([^;]+?)(?:\s+as\s+\w+)?\s*;\s*$/', trim($useStmt), $m)) {
                    $fqcn = trim($m[1]);
                    $existingUses[] = ['stmt' => $useStmt, 'fqcn' => $fqcn, 'token_idx' => $i];
                }
            }

            if (($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE || $tokens[$i][0] === T_TRAIT)
                && $tokens[$i][1] !== '__halt_compiler' && $classTokenPos === null) {
                $classTokenPos = $i;
            }
        }

        if ($classTokenPos === null) { echo "  ERROR no class declaration in: $path\n"; continue; }

        $classOffset = 0;
        for ($j = 0; $j < $classTokenPos; $j++) {
            if (is_array($tokens[$j])) {
                $classOffset += strlen($tokens[$j][1]);
            } else {
                $classOffset += strlen($tokens[$j]);
            }
        }

        $topLevelUses = [];
        $topLevelFqcns = [];
        foreach ($existingUses as $u) {
            $useOffset = 0;
            for ($j = 0; $j < $u['token_idx']; $j++) {
                if (is_array($tokens[$j])) {
                    $useOffset += strlen($tokens[$j][1]);
                } else {
                    $useOffset += strlen($tokens[$j]);
                }
            }
            if ($useOffset < $classOffset) {
                $topLevelUses[] = $u['stmt'];
                $topLevelFqcns[] = $u['fqcn'];
            }
        }

        $needsUse = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (!is_array($tokens[$i])) continue;
            if ($tokens[$i][0] !== T_STRING) continue;
            $name = $tokens[$i][1];
            if (in_array($name, $definedClasses)) continue;

            $classNs = $classMap[$name] ?? null;
            if ($classNs === null) continue;
            if ($classNs === $targetNs) continue;

            $fqcn = $classNs . '\\' . $name;
            if (in_array($fqcn, $topLevelFqcns)) continue;

            $prevIdx = $i - 1;
            while ($prevIdx >= 0 && is_array($tokens[$prevIdx]) && $tokens[$prevIdx][0] === T_WHITESPACE)
                $prevIdx--;
            $prevToken = $prevIdx >= 0 ? $tokens[$prevIdx] : null;
            $prevKind = is_array($prevToken) ? $prevToken[0] : null;

            $nextIdx = $i + 1;
            while ($nextIdx < count($tokens) && is_array($tokens[$nextIdx]) && $tokens[$nextIdx][0] === T_WHITESPACE)
                $nextIdx++;
            $nextToken = $nextIdx < count($tokens) ? $tokens[$nextIdx] : null;
            $nextKind = is_array($nextToken) ? $nextToken[0] : null;

            $shouldUse = false;
            if ($prevKind === T_EXTENDS || $prevKind === T_IMPLEMENTS
                || $prevKind === T_NEW || $prevKind === T_INSTANCEOF
                || $prevKind === T_CATCH) {
                $shouldUse = true;
            } elseif ($nextKind === T_DOUBLE_COLON) {
                $shouldUse = true;
            } elseif ($nextKind === T_VARIABLE) {
                $shouldUse = true;
            }

            if ($shouldUse) {
                $needsUse[$fqcn] = $name;
            }
        }

        $headerContent = substr($content, 0, $classOffset);
        $classContent = substr($content, $classOffset);

        $headerWithoutUses = $headerContent;
        foreach ($topLevelUses as $useStmt) {
            $headerWithoutUses = str_replace($useStmt, '', $headerWithoutUses);
        }
        $headerWithoutUses = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $headerWithoutUses);

        $guardPos = strpos($headerWithoutUses, "defined('SCRIPTLOG')");
        $guardLine = '';
        if ($guardPos !== false) {
            $guardEndPos = strpos($headerWithoutUses, "\n", $guardPos);
            if ($guardEndPos === false) $guardEndPos = strlen($headerWithoutUses);
            $guardLine = substr($headerWithoutUses, $guardPos, $guardEndPos - $guardPos);
        }

        $afterGuard = '';
        if ($guardPos !== false) {
            $guardEndPos = strpos($headerWithoutUses, "\n", $guardPos);
            if ($guardEndPos === false) $guardEndPos = strlen($headerWithoutUses);
            $afterGuard = trim(substr($headerWithoutUses, $guardEndPos + 1));
        } else {
            $phpPos = strpos($headerWithoutUses, '<?php');
            if ($phpPos !== false) {
                $afterGuard = trim(substr($headerWithoutUses, $phpPos + 5));
            }
        }

        $newHeader = '<?php' . "\n\n";
        $newHeader .= 'namespace ' . $targetNs . ';' . "\n";
        if (!empty($guardLine)) {
            $newHeader .= $guardLine . "\n";
        }
        if (!empty($afterGuard)) {
            $newHeader .= "\n" . $afterGuard . "\n";
        }

        $allUses = [];

        foreach ($topLevelFqcns as $i => $fqcn) {
            $allUses[$fqcn] = $topLevelUses[$i];
        }

        foreach ($needsUse as $fqcn => $shortName) {
            if (!isset($allUses[$fqcn])) {
                $allUses[$fqcn] = 'use ' . $fqcn . ';';
            }
        }

        ksort($allUses);

        if (!empty($allUses)) {
            $newHeader .= "\n";
            foreach ($allUses as $fqcn => $stmt) {
                $newHeader .= $stmt . "\n";
            }
        }

        $newHeader .= "\n";
        $newContent = $newHeader . $classContent;

        file_put_contents($path, $newContent);
        $processedFiles++;
        $newUseCount = count($needsUse);

        if ($newUseCount > 0) $filesWithUses++;

        echo "  OK: " . basename($path) . " (" . $targetNs . ")"
            . ($newUseCount > 0 ? ' +' . $newUseCount . ' uses' : '') . "\n";
    }
}

$aliasContent = "<?php\n\n/**\n * PSR-4 Backward Compatibility Aliases\n *\n * Auto-generated by lib/migrate-namespaces.php\n */\n\n";

ksort($aliases);
foreach ($aliases as $shortName => $fqcn) {
    $aliasContent .= "class_alias('{$fqcn}', '{$shortName}');\n";
}
$aliasContent .= "\n";

$aliasPath = __DIR__ . '/autoload-aliases.php';
if (file_exists($aliasPath)) copy($aliasPath, $aliasPath . '.bak');
file_put_contents($aliasPath, $aliasContent);

echo "\nGenerated aliases: $aliasPath (" . count($aliases) . " entries)\n";
echo "\n=== Migration Summary ===\n";
echo "Processed files: $processedFiles\n";
echo "Files with new use statements: $filesWithUses\n";
echo "Aliases generated: " . count($aliases) . "\n";
echo "Done.\n";
```

**Writing new code:**
```php
<?php
namespace Scriptlog\Controller\Api;

use Scriptlog\Core\ApiResponse;
use Scriptlog\Dao\PostDao;

class MyApiController extends ApiController
{
    // ...
}
```

**Key rule:** `class_alias()` does NOT help with unqualified type hints in namespaced files. Always add explicit `use` imports for cross-namespace classes.

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
            return $this->validateSinglePost($requestPath);
        case 'page':
            return $this->validatePage($requestPath);
        case 'category':
            return $this->validateCategory($requestPath);
        case 'archive':
            return $this->validateArchive($requestPath);
        case 'tag':
            return $this->validateTag($requestPath);
        // ... other cases
    }
}

private function validateSinglePost($requestPath)
{
    $postId = isset($requestPath->id) ? $requestPath->id : null;
    $postSlug = isset($requestPath->post) ? $requestPath->post : null;

    if (empty($postId) || empty($postSlug)) {
        return false;
    }

    $frontService = function_exists('front_service') ? front_service() : null;
    $post = $frontService ? $frontService->getPublishedPost((int) $postId) : null;

    if (empty($post) || !is_array($post)) {
        return false;
    }

    // Validate slug matches - redirect to 404 if slug is incorrect
    $dbSlug = isset($post['post_slug']) ? $post['post_slug'] : '';
    return ($dbSlug === $postSlug);
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
| **Validation** | Dispatcher uses `FrontService::searchTag()` (via `front_service()`) to verify posts exist |
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
- `lib/service/FrontService.php` - `searchTag()` method (via `front_service()`)
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
Scriptlog/
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
|   |-- navigation.php          # Navigation menu
|   |-- templates.php           # Theme management
|   |-- plugins.php             # Plugin management
|   |-- medialib.php            # Media library
|   |-- media-upload.php        # Media upload
|   |-- downloads.php           # Download management
|   |-- dashboard.php           # Dashboard
|   |-- export.php / import.php # Data import/export
|   |-- languages.php           # Language management
|   |-- translations.php        # Translation management
|   |-- privacy.php             # Privacy management
|   |-- signup.php              # User registration
|   |-- option-*.php            # Settings pages (general, permalink, mail, ...)
|   |-- ui/                     # Admin UI components
|   |-- plugins/                # Installed plugins (each in its own folder)
|   |   |-- index.php           # Plugin listing entry
|   |   +-- hello-world/        # Example plugin (plugin.ini + main class)
|   |-- assets/                 # Admin assets
|   +-- wysiwyg/                # Rich text editor
|
|-- api/                        # RESTful API
|   +-- index.php               # API entry point (/api/v1/)
|
|-- lib/                       # Core library
|   |-- main.php               # Application bootstrap
|   |-- common.php             # Constants (APP_ROOT, APP_ADMIN, ...)
|   |-- options.php            # PHP configuration
|   |-- Autoloader.php         # Legacy class autoloader
|   |-- utility-loader.php     # Utility functions loader
|   |-- autoload-aliases.php   # 232 class_alias() entries (reference only)
|   |-- autoload-aliases-map.php # Static alias map for lazy autoloader (232 entries)
|   |
|   +-- core/                  # Core classes - Scriptlog\Core (102 files)
|       |-- Bootstrap.php      # Application initialization, routes
|       |-- Dispatcher.php     # URL routing
|       |-- DbFactory.php      # PDO database connection
|       |-- Db.php             # PDO wrapper (prefix handling, dbQuery/dbInsert/...)
|       |-- Authentication.php # User authentication
|       |-- SessionMaker.php   # Custom session handler
|       |-- Dao.php            # DAO base class
|       |-- CSRFGuard.php      # CSRF protection
|       |-- ApiResponse.php    # API response handler
|       |-- ApiAuth.php        # API authentication
|       |-- ApiRouter.php      # API routing
|       |-- SearchFinder.php   # FULLTEXT search engine
|       +-- ...
|
|   +-- dao/                  # Data Access Objects - Scriptlog\Dao (19 files)
|       |-- PostDao.php       # Posts CRUD
|       |-- UserDao.php       # Users CRUD
|       |-- CommentDao.php    # Comments CRUD
|       |-- TopicDao.php      # Categories CRUD
|       |-- MediaDao.php      # Media CRUD
|       |-- PageDao.php       # Pages CRUD
|       |-- MenuDao.php       # Menus CRUD
|       |-- PluginDao.php     # Plugins CRUD
|       |-- ThemeDao.php      # Themes CRUD
|       +-- ConfigurationDao.php
|
|   +-- dto/                   # Data Transfer Objects - Scriptlog\Dto
|       |-- PostRequestDto.php
|       |-- UploadedFileDto.php
|       +-- api/               # API DTOs
|           |-- PostApiDto.php
|           |-- CommentApiDto.php
|           +-- TopicApiDto.php
|
|   +-- service/               # Business logic layer - Scriptlog\Service (23 files)
|       |-- PostService.php
|       |-- PostApplicationService.php
|       |-- ProtectedPostService.php
|       |-- UserService.php
|       |-- CommentService.php
|       |-- TopicService.php
|       |-- MediaService.php
|       |-- PageService.php
|       |-- MenuService.php
|       |-- PluginService.php
|       |-- ThemeService.php
|       |-- ConfigurationService.php
|       |-- ReplyService.php
|       |-- ScheduledPostService.php
|       |-- FrontService.php
|       |-- ConsentService.php
|       |-- DataRequestService.php
|       |-- DownloadService.php
|       |-- ExportService.php
|       |-- MigrationService.php
|       |-- LanguageService.php
|       |-- TranslationService.php
|       +-- NotificationService.php
|
|   +-- handler/               # Request handlers - Scriptlog\Handler (13 files)
|       |-- HandlerRegistry.php       # Handler registration & lookup
|       |-- FrontRequestHandler.php   # Frontend dispatch coordinator
|       |-- AdminActionRegistry.php   # Admin action registration & lookup
|       |-- AdminActionCommand.php    # Base class for admin action commands
|       |-- PostHandler.php
|       |-- PageHandler.php
|       |-- CategoryHandler.php
|       |-- TagHandler.php
|       |-- ArchiveHandler.php
|       |-- PrivacyHandler.php
|       |-- DownloadHandler.php
|       |-- BlogHandler.php
|       +-- HomeHandler.php
|
|       +-- admin/              # Admin action commands (35 *Cmd.php classes)
|           |-- comment/        # DeleteCommentCmd, EditCommentCmd, ListCommentsCmd
|           |-- media/          # DeleteMediaCmd, EditMediaCmd, ListMediaCmd, NewMediaCmd
|           |-- page/           # DeletePageCmd, EditPageCmd, ListPagesCmd, NewPageCmd
|           |-- plugin/         # ActivatePluginCmd, DeactivatePluginCmd, DeletePluginCmd, InstallPluginCmd, ListPluginsCmd
|           |-- post/           # DeletePostCmd, EditPostCmd, ListPostsCmd, NewPostCmd
|           |-- theme/          # ActivateThemeCmd, DeactivateThemeCmd, DeleteThemeCmd, EditThemeCmd, InstallThemeCmd, ListThemesCmd, NewThemeCmd
|           |-- topic/          # DeleteTopicCmd, EditTopicCmd, ListTopicsCmd, NewTopicCmd
|           +-- user/           # DeleteUserCmd, EditUserCmd, ListUsersCmd, NewUserCmd
|
|   +-- validator/              # Validators - Scriptlog\Validator (5 files)
|       |-- PostValidator.php
|       |-- FileUploadValidator.php
|       |-- ProtectedPostValidator.php
|       |-- CompositeValidator.php
|       +-- ValidationResult.php
|
|   +-- controller/            # Request controllers - Scriptlog\Controller (20 files)
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
|       |-- DownloadController.php
|       |-- DownloadAdminController.php
|       |-- ExportController.php
|       |-- ImportController.php
|       |-- LanguageController.php
|       |-- LocaleController.php
|       |-- SearchController.php
|       |-- TranslationController.php
|       |-- ApiController.php
|       |
|       +-- api/              # API Controllers - Scriptlog\Controller\Api (12 files)
|           |-- ApiController.php        # Public API info endpoint (GET /api/v1)
|           |-- PostsApiController.php
|           |-- CategoriesApiController.php
|           |-- CommentsApiController.php
|           |-- ArchivesApiController.php
|           |-- SearchApiController.php
|           |-- GdprApiController.php
|           |-- MediaApiController.php
|           |-- ProtectedPostApiController.php
|           |-- LanguagesApiController.php
|           |-- TranslationsApiController.php
|           +-- QueryApiController.php
|
|   +-- model/                # Data models - Scriptlog\Model (9 files)
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
|   +-- utility/              # Utility functions (224 files, dash-lowercase)
|       |-- app-config.php
|       |-- app-url.php
|       |-- csrf-defender.php
|       |-- remove-xss.php
|       |-- email-validation.php
|       +-- ...
|
|   +-- vendor/              # Composer dependencies
|
|-- benchmark/               # Performance benchmarks
|
|-- public/                  # Public web root
|   +-- themes/              # Theme templates
|       |-- blog/            # Default theme
|       |-- restoblog/
|       |-- tastybites/
|       +-- valdur/
|   +-- files/               # Uploaded files
|       |-- pictures/
|       |-- audio/
|       |-- video/
|       +-- docs/
|   +-- cache/               # Cache directory
|   +-- log/                 # Log directory
|
|-- docs/                      # Documentation
|   |-- dev-docs/              # Developer documentation
|   |   |-- DEVELOPER_GUIDE.md
|   |   |-- TESTING_GUIDE.md
|   |   |-- THEME_DEVELOPER_GUIDE.md
|   |   |-- PLUGIN_DEVELOPER_GUIDE.md
|   |   |-- API_DOCUMENTATION.md
|   |   |-- DATABASE_SCHEMA_GUIDE.md
|   |   |-- API_OPENAPI.yaml
|   |   +-- API_OPENAPI.json
|   +-- user-docs/             # End-user documentation
|
|-- tests/                   # PHPUnit test suites
|
|-- e2e/                     # End-to-end tests
|
+-- install/                  # Installation wizard
    |-- index.php
    |-- setup-db.php
    |-- validate-db.php
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
$app = Bootstrap::initialize(APP_ROOT);

// Returns an AppContext object exposing services via magic getters:
// - Database credentials
// - Services (authenticator, sessionMaker, userDao, etc.)
// Usage: $app->postDao, $app->configDao, $app->sanitizer, ...
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
    'search'   => "/search",
    'tag'      => "/tag/(?'tag'[\w\- ]+)",
    'privacy'  => "/privacy",
    'locale'   => "/locale",
    'download' => "/download/(?'identifier'[a-f0-9\-]+)",
    'download_file' => "/download/(?'identifier'[a-f0-9\-]+)/file"
];
```

| Route Key | Pattern | Description |
|-----------|---------|-------------|
| `home` | `/` | Homepage |
| `category` | `/category/{slug}` | Category archive pages |
| `archive` | `/archive/{mm}/{yyyy}` | Monthly archive pages |
| `archives` | `/archives` | Archive index |
| `blog` | `/blog*` | Blog listing |
| `page` | `/page/{slug}` | Static pages |
| `single` | `/post/{id}/{slug}` | Single post view |
| `search` | `/search` (permalinks ON) or `?q=` on app root (permalinks OFF) | Search results |
| `tag` | `/tag/{tag}` | Tag archive pages (supports spaces) |
| `privacy` | `/privacy` | Privacy policy page |
| `locale` | `/locale` | Locale/language switching |
| `download` | `/download/{identifier}` | Secure download (UUID) |
| `download_file` | `/download/{identifier}/file` | File download endpoint |

#### Content Validation

The Dispatcher validates content exists in the database before rendering templates to ensure proper 404 handling:

- Uses named parameters from route patterns (`id`, `page`, `category`)
- Checks the database via `FrontService` (through the `front_service()` helper)
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

The database is MySQL/MariaDB with InnoDB tables. The schema is **22 tables**. Every definition, index, default value, and seed insert lives in one file:

```
install/include/dbtable.php
```

The function `get_table_definitions($prefix)` returns the complete set. Production databases use a table prefix generated randomly during installation (e.g. `abc123_`), stored under `db.prefix` in the created config; the `tpglkl_` value in the stock `config.php` is only a fallback placeholder. The test database `blogware_test` uses unprefixed names.

All tables use `Engine=InnoDB DEFAULT CHARSET=utf8mb4`. The collation is `utf8mb4_general_ci` on every table except `tbl_api_keys`, which uses `utf8mb4_unicode_ci`. The only real `FOREIGN KEY` constraint is `tbl_api_keys.user_id` referencing `tbl_users.ID` with `ON DELETE CASCADE`; every other relationship is application-managed.

For the full column-by-column reference (data types, defaults, indexes, descriptions, DAO coverage, and how to change the schema), see `dev-docs/DATABASE_SCHEMA_GUIDE.md`.

### Users and authentication

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_users` | User accounts and login data | `ID`, `user_login` (unique), `user_email` (unique), `user_pass` (bcrypt), `user_level`, `user_reset_key`, `user_banned`, `login_time` |
| `tbl_user_token` | Persistent login tokens (remember me) | `ID`, `user_login`, `selector_hash`, `pwd_hash`, `is_expired`, `expired_date` |
| `tbl_login_attempt` | Failed login tracking by IP for rate limiting and lockout | `ip_address`, `login_date` |
| `tbl_api_keys` | REST API keys (only table with a real foreign key) | `id`, `user_id` (FK), `key_hash`, `expires_at`, `is_revoked` |

### Content

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_posts` | Blog posts and static pages | `ID`, `post_author`, `post_title`, `post_slug`, `post_content`, `post_status`, `post_type` (`blog`/`page`), `post_locale`, `comment_status`, `passphrase` |
| `tbl_topics` | Categories | `ID`, `topic_title`, `topic_slug`, `topic_status` (`Y`/`N`), `topic_locale` |
| `tbl_post_topic` | Many-to-many link between posts and topics | `post_id`, `topic_id` (composite PK) |
| `tbl_comments` | Comments with nested replies | `ID`, `comment_post_id`, `comment_parent_id`, `comment_status`, `comment_date` |

### Media and downloads

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_media` | Media library entries | `ID`, `media_filename`, `media_type`, `media_target`, `media_access`, `media_status` |
| `tbl_mediameta` | Key-value metadata for media items | `ID`, `media_id`, `meta_key`, `meta_value` |
| `tbl_media_download` | Secure download grants (UUID identifier, expiry, IP) | `ID`, `media_id`, `media_identifier` (unique UUID), `before_expired`, `ip_address` |
| `tbl_download_log` | Audit log of media downloads | `ID`, `media_id`, `media_identifier`, `ip_address`, `user_agent`, `status` |

### Navigation and configuration

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_menu` | Navigation menu items | `ID`, `menu_label`, `menu_link`, `menu_status`, `parent_id`, `menu_sort`, `menu_locale` |
| `tbl_settings` | Key-value configuration store | `ID`, `setting_name`, `setting_value` |
| `tbl_plugin` | Registered plugins | `ID`, `plugin_name`, `plugin_directory`, `plugin_status` |
| `tbl_themes` | Registered themes | `ID`, `theme_title`, `theme_designer`, `theme_directory`, `theme_status` |

### GDPR and privacy

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_consents` | Cookie and consent records | `ID`, `consent_type`, `consent_status`, `consent_ip`, `consent_date` |
| `tbl_data_requests` | GDPR access and erasure requests | `ID`, `request_type`, `request_email`, `request_status`, `request_date` |
| `tbl_privacy_logs` | Audit log of privacy actions | `ID`, `log_action`, `log_type`, `log_ip`, `log_date` |
| `tbl_privacy_policies` | Localized privacy policy content | `ID`, `locale` (unique), `policy_title`, `policy_content` |

### Internationalization

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `tbl_languages` | Supported languages (`en`, `ar`, `zh`, `fr`, `ru`, `es`, `id`) | `ID`, `lang_code` (unique), `lang_name`, `lang_direction`, `lang_is_default` |
| `tbl_translations` | UI translation strings per language | `ID`, `lang_id`, `translation_key`, `translation_value` (unique `lang_id` + `translation_key`) |

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
namespace Scriptlog\Dao;

use Scriptlog\Core\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * NewsletterDao extends Dao
 *
 * Demonstrates the standard DAO pattern: extend Scriptlog\Core\Dao,
 * use prefixed table names via $this->table(), and delegate to the
 * base class helpers (setSQL/findAll/findRow/create/modify/lastId).
 */
class NewsletterDao extends Dao
{
    /**
     * NewsletterDao constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Insert a subscriber.
     *
     * @param string $email
     * @return int Last insert ID
     */
    public function subscribe($email)
    {
        $this->create($this->table('newsletter'), [
            'subscriber_email' => $email
        ]);

        return $this->lastId();
    }

    /**
     * Mark a subscriber as unsubscribed.
     *
     * @param string $email
     * @return bool
     */
    public function unsubscribe($email)
    {
        $sql = "UPDATE " . $this->table('newsletter')
            . " SET status = 'unsubscribed', unsubscribe_at = NOW()"
            . " WHERE subscriber_email = ?";

        $this->setSQL($sql);
        $this->findAll([$email]);

        return true;
    }

    /**
     * List all active subscribers.
     *
     * @return array
     */
    public function getActiveSubscribers()
    {
        $sql = "SELECT * FROM " . $this->table('newsletter') . " WHERE status = 'active'";

        $this->setSQL($sql);

        return $this->findAll();
    }
}
```

> **Note:** The prefix-aware `table()` helper is a protected method on `Dao`, so table names are always written as `$this->table('newsletter')` - never hard-coded with the raw `tbl_` prefix. Queries are executed through the `Db` wrapper (`dbQuery`/`dbInsert`/`dbUpdate`), not raw `PDOStatement` calls.

#### Step 3: Service

```php
// lib/service/NewsletterService.php
namespace Scriptlog\Service;

use Scriptlog\Dao\NewsletterDao;

defined('SCRIPTLOG') || die("Direct access not permitted");

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
            throw new \InvalidArgumentException("Invalid email address");
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
namespace Scriptlog\Controller;

use Scriptlog\Service\NewsletterService;

defined('SCRIPTLOG') || die("Direct access not permitted");

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
>
> **Backward compatibility:** When creating a new namespaced class, add a `class_alias()` entry to `lib/autoload-aliases.php` and to the `$aliasMap` in `lib/autoload-aliases-map.php` so existing global-name references continue working.

---

## 7. Working with DAOs

### DAO Pattern Guidelines

| Guideline | Description |
|-----------|-------------|
| **Extends Dao** | DAOs extend the base `Scriptlog\Core\Dao` class for database connectivity |
| **Single Responsibility** | Each DAO handles one database table (an aggregate such as `PostDao` may also manage its relationship table, e.g. `tbl_post_topic`) |
| **CRUD Operations** | DAOs handle Create, Read, Update, Delete operations |
| **No Business Logic** | Keep validation in Services, not DAOs |
| **No SQL strings from user input** | `ORDER BY` values are resolved against an allow-list; all values are bound parameters |

### Dao Base Class

All DAOs extend the base `Dao` class at `lib/core/Dao.php` (namespace `Scriptlog\Core`). Its constructor resolves the `dbc` connection from `Registry` (throwing a `DbException` when it is absent) and stores the configured table prefix, which `table()` applies to raw table names:

```php
// lib/core/Dao.php (abridged signature list)
class Dao
{
    protected $dbc;        // Database connection (Db wrapper)
    protected $sql;        // Last set SQL query
    protected $error;      // Error tracking
    protected $sanitizing; // Sanitize instance (set by filteringId())
    protected $prefix;     // Configured table prefix

    public function __construct();  // Reads Registry::get('dbc'), sets $prefix

    protected function table($table);               // Apply table prefix
    protected function setSQL($sql);                // Set SQL query
    protected function findAll(array $data = [], $fetchMode = null);  // SELECT all rows
    protected function findRow(array $data = [], $fetchMode = null);  // SELECT one row
    protected function findColumn(array $data = [], $fetchMode = null); // SELECT one column
    public function checkCountValue(array $data = []): ?int;           // Row-count for SELECT
    protected function create($table, $params);          // INSERT via Db::dbInsert()
    protected function modify($table, $params, $where);  // UPDATE via Db::dbUpdate()
    protected function deleteRecord($table, $where, $limit = 1); // DELETE via Db::dbDelete()
    protected function replaceRecord($table, $params, $to);      // REPLACE via Db::dbReplace()
    protected function callTransaction();   // BEGIN (Db::dbTransaction())
    protected function callCommit();        // COMMIT (Db::dbCommit())
    protected function callRollBack();      // ROLLBACK (Db::dbRollBack())
    protected function closeConnection();   // Db::closeDbConnection()
    protected function lastId();            // Db::dbLastInsertId()
    protected function filteringId(Sanitize $sanitize, $str, $type); // Sanitize ID ('sql'|'xss')
}
```

> **Note:** The DAO layer is backed by the custom `Scriptlog\Core\Db` PDO wrapper (methods `dbQuery()`, `dbInsert()`, `dbUpdate()`, `dbDelete()`, `dbSelect()`, etc.) - not Medoo.

### PostDao Implementation

The actual `PostDao` class at `lib/dao/PostDao.php` extends `Scriptlog\Core\Dao`. It is the current reference implementation of the DAO pattern (926 lines). It declares three private constants used by the shared paginated queries, then a full set of CRUD, archive, search, and API helper methods:

```php
// lib/dao/PostDao.php (key methods, abridged)
namespace Scriptlog\Dao;

use Scriptlog\Core\Dao;
use Scriptlog\Core\DbException;
use Scriptlog\Core\LogError;
use Scriptlog\Core\Sanitize;

defined('SCRIPTLOG') || die("Direct access not permitted");

class PostDao extends Dao
{
    /**
     * Columns allowed in ORDER BY clauses to prevent SQL injection.
     * @var array
     */
    private const ALLOWED_SORT_COLUMNS = ['ID', 'post_date', 'post_title', 'post_modified'];

    /**
     * Shared WHERE fragment filtering to published, public blog posts.
     * @var string
     */
    private const PUBLISHED_FILTER = "p.post_status = 'publish' AND p.post_visibility = 'public'";

    /**
     * Shared SELECT column list for paginated published-post queries.
     * @var string
     */
    private const SELECT_PUBLISHED_COLUMNS = "p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                   p.post_title, p.post_slug, p.post_summary, p.post_status,
                   p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                   u.user_login as author_login, u.user_fullname as author_name";

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * findPosts - retrieve all blog records from tbl_posts.
     *
     * @param string $orderBy       Sort column (whitelisted)
     * @param int|null $author      Filter by author ID
     * @param bool $onlyPublished   Only published + public posts
     * @return array
     * @throws DbException
     */
    public function findPosts(string $orderBy = 'ID', ?int $author = null, bool $onlyPublished = true): array
    {
        $sortColumn = $this->resolveSortColumn($orderBy);

        $sql = "SELECT p.ID,
            p.media_id,
            p.post_author,
            p.post_date,
            p.post_modified,
            p.post_title,
            p.post_slug,
            p.post_content,
            p.post_status,
            p.post_visibility,
            p.post_password,
            p.post_tags,
            p.post_headlines,
            p.post_type,
            p.post_locale,
            p.passphrase,
            u.user_login
FROM tbl_posts AS p
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.post_type = 'blog'";

        $data = [];

        if (!is_null($author)) {
            $sql .= " AND p.post_author = ?";
            $data[] = (int)$author;
        }

        if ($onlyPublished) {
            $sql .= " AND " . self::PUBLISHED_FILTER;
        }

        $sql .= " ORDER BY p.$sortColumn DESC";

        $this->setSQL($sql);

        $posts = $this->findAll($data);

        return (empty($posts)) ? [] : $posts;
    }

    /**
     * findPost - retrieve a single post record by ID.
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @param int|null $author
     * @param bool $onlyPublished
     * @return array|null
     * @throws DbException
     * @throws \InvalidArgumentException
     */
    public function findPost(int $ID, Sanitize $sanitize, ?int $author = null, bool $onlyPublished = true): ?array
    {
        $idsanitized = $this->filteringId($sanitize, (string)$ID, 'sql');

        $sql = "SELECT ID,
            media_id,
            post_author,
            post_date,
            post_modified,
            post_title,
            post_slug,
            post_content,
            post_summary,
            post_status,
            post_visibility,
            post_password,
            post_tags,
            post_headlines,
            post_locale,
            comment_status,
            passphrase
FROM tbl_posts
WHERE ID = ? AND post_type = 'blog'";

        $data = [$idsanitized];

        if (!is_null($author)) {
            $sql .= " AND post_author = ?";
            $data[] = (int)$author;
        }

        if ($onlyPublished) {
            $sql .= " AND post_status = 'publish' AND post_visibility = 'public'";
        }

        $this->setSQL($sql);

        $postDetail = $this->findRow($data);

        return (empty($postDetail)) ? null : $postDetail;
    }

    /**
     * createPost - insert a new post record together with its topic relationships.
     *
     * @param array $bind      Post column => value pairs
     * @param int|array $topicId  Topic/category ID(s)
     * @return int  New post ID
     * @throws \InvalidArgumentException
     */
    public function createPost(array $bind, $topicId): int
    {
        $data = [
           'post_author' => $bind['post_author'],
           'post_date' => $bind['post_date'],
           'post_title' => $bind['post_title'],
           'post_slug' => $bind['post_slug'],
           'post_content' => $bind['post_content'],
           'post_summary' => $bind['post_summary'],
           'post_status' => $bind['post_status'],
           'post_visibility' => $bind['post_visibility'],
           'post_password' => $bind['post_password'],
           'post_tags' => $bind['post_tags'],
           'post_headlines' => $bind['post_headlines'],
           'post_locale' => $bind['post_locale'] ?? 'en',
           'comment_status' => $bind['comment_status'],
           'passphrase' => $bind['passphrase']
        ];

        if (!empty($bind['media_id'])) {
            $data['media_id'] = $bind['media_id'];
        }

        $this->create("tbl_posts", $data);

        $postId = (int)$this->lastId();

        foreach ((array)$topicId as $topic_id) {
            $this->create("tbl_post_topic", [
              'post_id' => $postId,
              'topic_id' => $topic_id]);
        }

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }

        return $postId;
    }

    /**
     * updatePost - update an existing post record (transactional) together with
     * its topic relationships. Old relationships are deleted and re-created.
     *
     * @param Sanitize $sanitize
     * @param array $bind
     * @param int $ID
     * @param int|array $topicId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function updatePost(Sanitize $sanitize, array $bind, int $ID, $topicId): void
    {
        $cleanId = $this->filteringId($sanitize, (string)$ID, 'sql');

        try {
            $this->callTransaction();

            $updateData = [
                'post_author' => $bind['post_author'],
                'post_modified' => $bind['post_modified'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_visibility' => $bind['post_visibility'],
                'post_tags' => $bind['post_tags'],
                'post_headlines' => $bind['post_headlines'],
                'post_locale' => $bind['post_locale'] ?? 'en',
                'comment_status' => $bind['comment_status']
            ];

            if (!empty($bind['post_password'])) {
                $updateData['post_password'] = $bind['post_password'];
            }
            if (!empty($bind['passphrase'])) {
                $updateData['passphrase'] = $bind['passphrase'];
            }
            if (!empty($bind['media_id'])) {
                $updateData['media_id'] = $bind['media_id'];
            }

            $this->modify("tbl_posts", $updateData, ['ID' => (int)$cleanId]);

            $this->deleteRecord("tbl_post_topic", ['post_id' => (int)$cleanId], null);

            foreach ((array)$topicId as $topic_id) {
                $this->create("tbl_post_topic", [
                    'post_id' => $cleanId,
                    'topic_id' => $topic_id
                ]);
            }

            $this->callCommit();

            if (function_exists('page_cache_clear')) {
                page_cache_clear();
            }
        } catch (DbException $e) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(500);
            LogError::exceptionHandler($e);
        } catch (\Throwable $th) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(500);
            LogError::exceptionHandler($th);
        }
    }

    /**
     * deletePost - delete a post record by ID.
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @return void
     * @throws \InvalidArgumentException
     */
    public function deletePost(int $ID, Sanitize $sanitize): void
    {
        $cleanId = $this->filteringId($sanitize, (string)$ID, 'sql');
        $this->deleteRecord("tbl_posts", ['ID' => $cleanId]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * anonymizePostAuthor - reassign all posts of a deleted user to the
     * primary administrator (ID 1). Used for GDPR "Right to be Forgotten".
     *
     * @param int $authorId
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function anonymizePostAuthor(int $authorId): bool
    {
        $this->modify("tbl_posts", ['post_author' => 1], ['post_author' => $authorId]);

        return true;
    }

    /**
     * checkPostId - verify a blog post record exists.
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @return bool
     * @throws \InvalidArgumentException
     * @throws DbException
     */
    public function checkPostId(int $ID, Sanitize $sanitize): bool
    {
        $idsanitized = $this->filteringId($sanitize, (string)$ID, 'sql');

        $sql = "SELECT ID FROM tbl_posts WHERE ID = ? AND post_type = 'blog'";

        $this->setSQL($sql);

        $stmt = $this->checkCountValue([$idsanitized]);

        return $stmt > 0;
    }

    /**
     * totalPostRecords - total blog post records (optionally per author).
     *
     * @param int|null $author
     * @return int
     * @throws DbException
     */
    public function totalPostRecords(?int $author = null): int
    {
        $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'blog'";

        $data = [];

        if (!is_null($author)) {
            $sql = "SELECT ID FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'";
            $data[] = $author;
        }

        $this->setSQL($sql);

        return $this->checkCountValue($data) ?? 0;
    }

    /**
     * Resolve a safe ORDER BY column from a user-supplied sort key.
     * Falls back to 'ID' when the requested column is not whitelisted.
     *
     * @param string $sortBy
     * @return string
     */
    private function resolveSortColumn(string $sortBy): string
    {
        $allowedColumns = self::ALLOWED_SORT_COLUMNS;

        return in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
    }

    /**
     * Resolve a safe ORDER BY direction ('ASC' or 'DESC').
     * Any value other than ASC falls back to DESC.
     *
     * @param string $sortOrder
     * @return string
     */
    private function resolveSortDirection(string $sortOrder): string
    {
        return strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    }
}
```

> **Note:** The legacy `dropDownPostStatus()`, `dropDownCommentStatus()`, `dropDownVisibility()`, and `dropDownLocale()` helpers were removed from `PostDao`; the admin post form now renders these selects through utility functions (`post_status_dropdown()`, `comment_status_dropdown()`, `post_visibility_dropdown()`, `post_locale_dropdown()`), called directly from `PostController`. The remaining `PostDao` methods (pagination, archive, search, and API helpers) are listed in the table below.

### Key Methods in PostDao

| Method | Purpose | Signature highlights |
|--------|---------|---------------------|
| `findPosts()` | Get all blog posts with filters | `(string $orderBy = 'ID', ?int $author = null, bool $onlyPublished = true): array` |
| `findPublishedPostsPaginated()` | Paginated published posts (API) | `(int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC', ?int $author = null): array` |
| `countPublishedPosts()` | Count published posts (API) | `(?int $author = null): int` |
| `findPublishedPostById()` | Single published post by ID (API) | `(int $postId): ?array` |
| `getPostById()` | Post by ID, any status (API) | `(int $postId): ?array` |
| `findPost()` | Single post by ID with filters | `(int $ID, Sanitize $sanitize, ?int $author = null, bool $onlyPublished = true): ?array` |
| `createPost()` | Insert post + topic relationships | `(array $bind, $topicId): int` |
| `updatePost()` | Update post + topic relationships (transactional) | `(Sanitize $sanitize, array $bind, int $ID, $topicId): void` |
| `deletePost()` | Delete post | `(int $ID, Sanitize $sanitize): void` |
| `anonymizePostAuthor()` | GDPR: reassign posts to admin (ID 1) | `(int $authorId): bool` |
| `checkPostId()` | Verify blog post exists | `(int $ID, Sanitize $sanitize): bool` |
| `totalPostRecords()` | Count blog posts (optionally per author) | `(?int $author = null): int` |
| `findArchiveIndex()` | Year/month archive index with counts | `(): array` |
| `findPostsByYear()` | Paginated posts for a year (API) | `(int $year, int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC'): array` |
| `countPostsByYear()` | Count posts in a year (API) | `(int $year): int` |
| `findPostsByYearMonth()` | Paginated posts for year+month (API) | `(int $year, int $month, int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC'): array` |
| `countPostsByYearMonth()` | Count posts in year+month (API) | `(int $year, int $month): int` |
| `searchPostsApi()` | LIKE search of posts/pages (API) | `(string $keyword, string $type = 'all', int $limit = 50): array` |
| `findTopicsByPostId()` | Topics attached to a post | `(int $postId): array` |
| `deletePostTopics()` | Delete all topic relationships | `(int $postId): void` |
| `setPostTopics()` | Replace all topic relationships (API) | `(int $postId, array $topicIds): void` |
| `deletePostComments()` | Delete all comments for a post | `(int $postId): void` |
| `insertPostApi()` | Insert post from raw data (API) | `(array $data): int` |
| `updatePostApi()` | Update specific post fields (API) | `(int $postId, array $data): void` |

Private helpers: `resolveSortColumn(string $sortBy): string` and `resolveSortDirection(string $sortOrder): string` whitelist `ORDER BY` input against `ALLOWED_SORT_COLUMNS` to prevent SQL injection.

### Database Columns in tbl_posts

Source of truth: `install/include/dbtable.php` (engine `InnoDB`, charset `utf8mb4`).

| Column | Type | Description |
|--------|------|-------------|
| `ID` | BIGINT(20) unsigned | Primary key (auto-increment) |
| `media_id` | BIGINT(20) unsigned | Featured image (`0` when none) |
| `post_author` | BIGINT(20) unsigned | Author (FK to `tbl_users.ID`) |
| `post_date` | DATETIME | Creation date |
| `post_modified` | DATETIME | Last modified (nullable) |
| `post_title` | TINYTEXT | Post title |
| `post_slug` | VARCHAR(255) | URL slug (indexed, `idx_post_slug`) |
| `post_content` | LONGTEXT | Full content |
| `post_summary` | MEDIUMTEXT | Short summary (meta description) |
| `post_keyword` | TEXT | SEO meta keywords (nullable) |
| `post_status` | VARCHAR(20) | `publish` / `draft` |
| `post_visibility` | VARCHAR(20) | `public` / `private` / `protected` |
| `post_password` | VARCHAR(255) | Bcrypt password hash (protected posts) |
| `post_tags` | TEXT | Comma-separated tags |
| `post_headlines` | INT(5) | Headline/slideshow flag (`0`/`1`) |
| `post_sticky` | INT(5) | Sticky post flag (`0`/`1`) |
| `post_type` | VARCHAR(120) | `blog` (default) or `page` |
| `post_locale` | VARCHAR(10) | Language code, default `en` (indexed, `idx_post_locale`) |
| `comment_status` | VARCHAR(20) | `open` / `closed` |
| `passphrase` | VARCHAR(255) | SHA-256 passphrase `hash('sha256', app_key() . password)` used for AES content encryption |

Indexes: `PRIMARY KEY (ID)`, `KEY author_id (post_author)`, `KEY post_media (media_id)`, `KEY idx_post_slug (post_slug)`, `KEY idx_post_locale (post_locale)`, `FULLTEXT KEY (post_tags, post_title, post_content)`.

### Other DAOs

| DAO | File | Purpose |
|-----|------|---------|
| `UserDao` | `lib/dao/UserDao.php` | User CRUD |
| `CommentDao` | `lib/dao/CommentDao.php` | Comment CRUD |
| `ReplyDao` | `lib/dao/ReplyDao.php` | Nested comment replies |
| `TopicDao` | `lib/dao/TopicDao.php` | Category CRUD |
| `PostTopicDao` | `lib/dao/PostTopicDao.php` | Post-topic relationships |
| `MediaDao` | `lib/dao/MediaDao.php` | Media CRUD |
| `PageDao` | `lib/dao/PageDao.php` | Page CRUD |
| `MenuDao` | `lib/dao/MenuDao.php` | Menu CRUD |
| `PluginDao` | `lib/dao/PluginDao.php` | Plugin CRUD |
| `ThemeDao` | `lib/dao/ThemeDao.php` | Theme CRUD |
| `ConfigurationDao` | `lib/dao/ConfigurationDao.php` | System settings CRUD |
| `ConsentDao` | `lib/dao/ConsentDao.php` | GDPR consent records |
| `DataRequestDao` | `lib/dao/DataRequestDao.php` | GDPR data requests |
| `PrivacyLogDao` | `lib/dao/PrivacyLogDao.php` | Privacy audit logs |
| `PrivacyPolicyDao` | `lib/dao/PrivacyPolicyDao.php` | Privacy policy versions |
| `LanguageDao` | `lib/dao/LanguageDao.php` | Language definitions |
| `TranslationDao` | `lib/dao/TranslationDao.php` | Translation key/value pairs |
| `UserTokenDao` | `lib/dao/UserTokenDao.php` | Persistent auth tokens / password reset |

That is **19 DAO classes** in total. There is **no** dedicated DAO for `tbl_login_attempt`, `tbl_api_keys`, `tbl_mediameta`, `tbl_media_download`, or `tbl_download_log` - those tables are accessed via core classes, services, and utility functions (e.g. login throttling in `Authentication`, API keys in `lib/core/` middleware, media metadata/downloads via `MediaService`).

---

## 8. Working with Services

### Service Layer Guidelines

| Principle | Description |
|-----------|-------------|
| **Business Logic** | Services contain business logic and input handling |
| **Setter Methods** | Services use setters to prepare data before passing to DAO |
| **Data Access** | Services call DAOs for database operations |
| **Sanitization** | Services sanitize and purify input |
| **Validation** | Services validate using FormValidator |

### PostService Implementation

The actual `PostService` class at `lib/service/PostService.php` manages post business logic:

```php
// lib/service/PostService.php
namespace Scriptlog\Service;

use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Core\Session;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\TopicDao;

defined('SCRIPTLOG') || die("Direct access not permitted");

class PostService
{
    private $postId;
    private $post_image;
    private $author;
    private $post_date;
    private $post_modified;
    private $title;
    private $slug;
    private $content;
    private $meta_desc;
    private $post_status;
    private $post_visibility;
    private $post_password;
    private $post_headlines;
    private $comment_status;
    private $passphrase;
    private $topics;
    private $tags;
    private $post_locale;

    private $postDao;
    private $validator;
    private $sanitizer;

    /**
     * Constructor
     * @param PostDao $postDao
     * @param FormValidator $validator
     * @param Sanitize $sanitizer
     */
    public function __construct(PostDao $postDao, FormValidator $validator, Sanitize $sanitizer)
    {
        $this->postDao = $postDao;
        $this->validator = $validator;
        $this->sanitizer = $sanitizer;
    }

    // Setter methods for post properties
    
    public function setPostId($postId) { $this->postId = $postId; }
    
    public function setPostImage($post_image) { $this->post_image = $post_image; }
    
    public function setPostAuthor($author) { $this->author = $author; }
    
    public function setPostDate($date_created) { $this->post_date = $date_created; }
    
    public function setPostModified($date_modified) { $this->post_modified = $date_modified; }
    
    public function setPostTitle($title) { 
        $this->title = prevent_injection($title); 
    }
    
    public function setPostSlug($slug) { 
        $this->slug = make_slug($slug); 
    }
    
    public function setPostContent($content, $skipPurify = false) { 
        $this->content = $skipPurify ? $content : purify_dirty_html($content); 
    }
    
    public function setMetaDesc($meta_desc) { 
        $this->meta_desc = prevent_injection($meta_desc); 
    }
    
    public function setPublish($post_status) { $this->post_status = $post_status; }
    
    public function setVisibility($post_visibility) { $this->post_visibility = $post_visibility; }
    
    public function setProtected($post_password) { $this->post_password = $post_password; }
    
    public function setHeadlines($post_headlines) { $this->post_headlines = $post_headlines; }
    
    public function setComment($comment_status) { $this->comment_status = $comment_status; }
    
    public function setPassPhrase($passphrase) { 
        $this->passphrase = hash('sha256', app_key() . $passphrase); 
    }
    
    public function setTopics($topics) { $this->topics = $topics; }
    
    public function setPostTags($tags) { $this->tags = $tags; }
    
    public function setPostLocale($post_locale) { 
        $this->post_locale = sanitize_locale($post_locale); 
    }

    /**
     * Retrieve all posts
     * @param string $orderBy
     * @param int|null $author
     * @return array
     */
    public function grabPosts($orderBy = 'ID', $author = null)
    {
        return $this->postDao->findPosts($orderBy, $author, false);
    }

    /**
     * Retrieve single post by ID
     * @param int $postId
     * @return array|bool
     */
    public function grabPost($postId)
    {
        return $this->postDao->findPost($postId, $this->sanitizer, null, false);
    }

    /**
     * Insert new post
     * @return int New post ID
     */
    public function addPost()
    {
        $category = new TopicDao();

        $this->validator->sanitize($this->author, 'int');
        $this->validator->sanitize($this->post_image, 'int');
        $this->validator->sanitize($this->title, 'string');

        if ((!empty($this->meta_desc)) || (!empty($this->tags))) {
            $this->validator->sanitize($this->meta_desc, 'string');
        }

        $topic_id = $this->topics;

        // Auto-create "Uncategorized" if no topic selected
        if ($this->topics == 0) {
            $categoryId = $category->createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);
            $getCategory = $category->findTopicById($categoryId, $this->sanitizer, \PDO::FETCH_ASSOC);

            $topic_id = isset($getCategory['ID']) ? abs((int)$getCategory['ID']) : 0;
        }

        $new_post = [
            'media_id' => $this->post_image,
            'post_author' => $this->author,
            'post_date' => $this->post_date,
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_status' => $this->post_status,
            'post_visibility' => $this->post_visibility,
            'post_password' => $this->post_password,
            'post_tags' => $this->tags,
            'post_headlines' => $this->post_headlines,
            'post_locale' => $this->post_locale ?? 'en',
            'comment_status' => $this->comment_status,
            'passphrase' => $this->passphrase
        ];

        return $this->postDao->createPost($new_post, $topic_id);
    }

    /**
     * Update existing post
     * @return void
     */
    public function modifyPost()
    {
        $this->validator->sanitize($this->postId, 'int');
        $this->validator->sanitize($this->author, 'int');
        $this->validator->sanitize($this->post_image, 'int');
        $this->validator->sanitize($this->title, 'string');

        if ((!empty($this->meta_desc)) || (!empty($this->tags))) {
            $this->validator->sanitize($this->meta_desc, 'string');
            $this->validator->sanitize($this->tags, 'string');
        }

        $postData = [
            'post_author' => $this->author,
            'post_modified' => $this->post_modified,
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_status' => $this->post_status,
            'post_visibility' => $this->post_visibility,
            'post_password' => $this->post_password,
            'post_tags' => $this->tags,
            'post_headlines' => $this->post_headlines,
            'post_locale' => $this->post_locale ?? 'en',
            'comment_status' => $this->comment_status,
            'passphrase' => $this->passphrase
        ];

        if (!empty($this->post_image)) {
            $postData['media_id'] = $this->post_image;
        }

        $this->postDao->updatePost($this->sanitizer, $postData, $this->postId, $this->topics);
    }

    /**
     * Delete post and associated media
     */
    public function removePost()
    {
        (version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

        $this->validator->sanitize($this->postId, 'int');

        $data_post = $this->postDao->findPost($this->postId, $this->sanitizer);
        if (!$data_post) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return false;
        }

        $media_id = $data_post['media_id'] ?? 0;

        // Delete associated media files
        if (class_exists('MediaDao')) {
            $medialib = new MediaDao();

            if (method_exists($medialib, 'findMediaBlog') && $media_id) {
                $media_data = $medialib->findMediaBlog((int)$media_id);
                $media_filename = isset($media_data['media_filename']) &&
                    preg_match('/^[a-zA-Z0-9_\-\.]+$/', $media_data['media_filename']) ?
                    basename($media_data['media_filename']) : '';

                if (!empty($media_filename)) {
                    $base_path = __DIR__ . '/../../' . APP_IMAGE;
                    $files_to_delete = [
                        $base_path . $media_filename,
                        $base_path . APP_IMAGE_LARGE . 'large_' . $media_filename,
                        $base_path . APP_IMAGE_MEDIUM . 'medium_' . $media_filename,
                        $base_path . APP_IMAGE_SMALL . 'small_' . $media_filename,
                    ];

                    foreach ($files_to_delete as $file) {
                        if (file_exists($file) && is_writable($file)) {
                            unlink($file);
                        }
                    }

                    if (method_exists($medialib, 'deleteMedia')) {
                        $medialib->deleteMedia((int) $media_id, $this->sanitizer);
                    }
                }
            }
        }

        $this->postDao->deletePost($this->postId, $this->sanitizer);
    }

    public function postAuthorId()
    {
        if (isset(Session::getInstance()->scriptlog_session_id)) {
            return Session::getInstance()->scriptlog_session_id;
        }
        return false;
    }

    public function postAuthorLevel()
    {
        return user_privilege();
    }

    public function totalPosts(array $data = []): ?int
    {
        $author = isset($data[0]) ? (int)$data[0] : null;

        return $this->postDao->totalPostRecords($author);
    }
}
```

### Key Methods in PostService

| Category | Method | Purpose |
|----------|--------|---------|
| **Setters** | `setPostTitle()`, `setPostSlug()`, `setPostContent()` (2nd arg `$skipPurify`), etc. | Prepare post properties |
| **Retrieval** | `grabPosts()` | Get all posts |
| **Retrieval** | `grabPost()` | Get single post by ID |
| **CRUD** | `addPost()` | Create new post |
| **CRUD** | `modifyPost()` | Update existing post |
| **CRUD** | `removePost()` | Delete post and media |
| **User** | `postAuthorId()` | Get current author ID |
| **User** | `postAuthorLevel()` | Get current author level |
| **Count** | `totalPosts()` | Count total posts |
| **API** | `getPublishedPostsApi()` / `countPublishedPostsApi()` / `getPublishedPostApi()` | Paginated published posts |
| **API** | `getArchiveIndexApi()`, `getPostsByYearApi()`, `countPostsByYearApi()`, `getPostsByYearMonthApi()`, `countPostsByYearMonthApi()` | Archive data |
| **API** | `searchPostsApi()`, `getPostTopicsApi()`, `setPostTopicsApi()` | Search + topic mapping |
| **API** | `createPostApi()`, `updatePostApi()`, `removePostApi()`, `getPostByIdApi()` | Post CRUD via API |
| **Media** | `processPostImage()` (private `processDefaultImage()` / `processUploadedImage()`) | Featured image processing |

The legacy `postStatusDropDown()`, `commentStatusDropDown()`, `visibilityDropDown()`, and `localeDropDown()` methods were removed from `PostService` (along with the DAO dropdown methods) - the admin form now calls `post_status_dropdown()`, `comment_status_dropdown()`, `post_visibility_dropdown()`, and `post_locale_dropdown()` utilities directly from the controller.

### Input Sanitization in PostService

The service uses various utility functions for sanitization:

| Function | Purpose |
|----------|---------|
| `prevent_injection()` | Prevent SQL injection |
| `make_slug()` | Generate URL-friendly slug |
| `purify_dirty_html()` | Clean HTML content (htmLawed) |
| `sanitize_locale()` | Validate locale code |

### Service Dependencies

PostService depends on:
- `PostDao` - Database operations
- `FormValidator` - Input validation  
- `Sanitize` - ID sanitization
- `TopicDao` - Auto-create categories
- `MediaDao` - Media cleanup on delete

### Other Services

| Service | File | Purpose |
|---------|------|---------|
| `UserService` | `lib/service/UserService.php` | User business logic |
| `CommentService` | `lib/service/CommentService.php` | Comment handling |
| `TopicService` | `lib/service/TopicService.php` | Category logic |
| `MediaService` | `lib/service/MediaService.php` | Media handling |
| `PageService` | `lib/service/PageService.php` | Page logic |
| `MenuService` | `lib/service/MenuService.php` | Menu logic |
| `PluginService` | `lib/service/PluginService.php` | Plugin logic |
| `ThemeService` | `lib/service/ThemeService.php` | Theme logic |
| `ConfigurationService` | `lib/service/ConfigurationService.php` | Settings |
| `ReplyService` | `lib/service/ReplyService.php` | Reply logic |
| `ScheduledPostService` | `lib/service/ScheduledPostService.php` | Promotes due scheduled posts at bootstrap |
| `PostApplicationService` | `lib/service/PostApplicationService.php` | Post orchestration (create/update with media, encryption, headlines) |
| `ProtectedPostService` | `lib/service/ProtectedPostService.php` | Password-protected post resolution and content sanitization |
| `FrontService` | `lib/service/FrontService.php` | Frontend post/page/archive queries |
| `ConsentService` | `lib/service/ConsentService.php` | GDPR consent management |
| `DataRequestService` | `lib/service/DataRequestService.php` | GDPR data request handling |
| `DownloadService` | `lib/service/DownloadService.php` | Secure file downloads |
| `ExportService` | `lib/service/ExportService.php` | Content export (WordPress, Ghost, etc.) |
| `MigrationService` | `lib/service/MigrationService.php` | Content import logic |
| `LanguageService` | `lib/service/LanguageService.php` | Language management |
| `TranslationService` | `lib/service/TranslationService.php` | Translation management |
| `NotificationService` | `lib/service/NotificationService.php` | Email notifications |

That is **23 services** in total. Privacy audit logging is handled by the `PrivacyLogDao` (used by `DataRequestService`), not by a dedicated `PrivacyLogService`.

---

## 9. Working with Controllers

### Controller Guidelines

| Guideline | Description |
|-----------|-------------|
| **Extends BaseApp** | Controllers extend `BaseApp` for view/viewtitle handling |
| **HTTP Handling** | Controllers handle HTTP requests (POST, GET) |
| **Service Calls** | Controllers call Services for business logic |
| **View Rendering** | Controllers render views using View class |
| **Session Messages** | Controllers set session status/error messages |

### PostController Implementation (Thinned - Phase 3)

The `PostController` was refactored in Phase 3 to delegate business logic to `PostApplicationService`. The controller now handles **only** HTTP/security, validation, view rendering, and simple delegation:

```php
// lib/controller/PostController.php (thinned - 430 lines, down from ~656)
namespace Scriptlog\Controller;

use Scriptlog\Core\ActionConst;
use Scriptlog\Core\AppException;
use Scriptlog\Core\BaseApp;
use Scriptlog\Core\LogError;
use Scriptlog\Core\View;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\TopicDao;
use Scriptlog\Dto\PostRequestDto;
use Scriptlog\Dto\UploadedFileDto;
use Scriptlog\Service\PostApplicationService;
use Scriptlog\Service\PostService;
use Scriptlog\Validator\FileUploadValidator;
use Scriptlog\Validator\PostValidator;
use Scriptlog\Validator\ProtectedPostValidator;

class PostController extends BaseApp
{
    private $view;
    private $postService;
    private $topicDao;
    private $mediaDao;
    private $appService;

    public function __construct(PostService $postService, TopicDao $topicDao, MediaDao $mediaDao, PostApplicationService $appService)
    {
        $this->postService = $postService;
        $this->topicDao = $topicDao;
        $this->mediaDao = $mediaDao;
        $this->appService = $appService;
    }

    public function listItems()
    {
        // Session status/error handling
        // Delegates to $this->postService for data
        // Renders 'all-posts' view
    }

    public function insert()
    {
        $errors = array();
        $checkError = true;
        $user_level = $this->postService->postAuthorLevel();
        $topics = $this->topicDao;
        $medialib = $this->mediaDao;

        if (isset($_POST['postFormSubmit'])) {
            $mediaFile = UploadedFileDto::fromGlobals();
            $file_location = $mediaFile->tmpName;
            $file_type = $mediaFile->type;
            $file_name = $mediaFile->name;
            $file_size = $mediaFile->size;
            $file_error = $mediaFile->error;

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();                // CSRF validation
                $this->checkPostPayload();              // Form key whitelist
                $checkError = $this->validatePostSubmission($file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderNewPostForm($errors, $_POST, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                // Delegate ALL business logic to PostApplicationService
                $this->appService->createPost($file_location, $file_type, $file_name,
                    $file_size, $file_extension, $new_filename, $user_level);

                $_SESSION['status'] = "postAdded";
                direct_page('index.php?load=posts&status=postAdded', 200);
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            }
        }

        $this->renderNewPostForm(null, null, $topics, $medialib, $user_level);
        return $this->view->render();
    }

    public function update($id)
    {
        $errors = array();
        $checkError = true;
        $user_level = $this->postService->postAuthorLevel();
        $topics = $this->topicDao;
        $medialib = $this->mediaDao;

        $getPost = $this->postService->grabPost($id);
        if (!$getPost) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
        }

        $data_post = array(
          'ID' => $getPost['ID'],
          'media_id' => $getPost['media_id'],
          'post_author' => $getPost['post_author'],
          'post_date' => $getPost['post_date'],
          'post_modified' => $getPost['post_modified'],
          'post_title' => $getPost['post_title'],
          'post_content' => $getPost['post_content'],
          'post_summary' => $getPost['post_summary'],
          'post_status' => $getPost['post_status'],
          'post_visibility' => $getPost['post_visibility'],
          'post_password' => $getPost['post_password'],
          'post_tags' => $getPost['post_tags'],
          'post_headlines' => $getPost['post_headlines'],
          'comment_status' => $getPost['comment_status'],
          'passphrase' => $getPost['passphrase']
        );

        if (isset($_POST['postFormSubmit'])) {
            $mediaFile = UploadedFileDto::fromGlobals();
            $file_location = $mediaFile->tmpName;
            $file_type = $mediaFile->type;
            $file_name = $mediaFile->name;
            $file_size = $mediaFile->size;
            $file_error = $mediaFile->error;

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();
                $this->checkPostUpdatePayload();
                $checkError = $this->validatePostUpdate($file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderEditPostForm($errors, $data_post, $getPost, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                // Delegate ALL business logic to PostApplicationService
                $this->appService->updatePost((int)$id, $file_location, $file_type,
                    $file_name, $file_size, $file_extension, $new_filename,
                    $user_level, $data_post['media_id']);

                $_SESSION['status'] = "postUpdated";
                direct_page('index.php?load=posts&status=postUpdated', 200);
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            }
        }

        $this->renderEditPostForm(null, $data_post, $getPost, $topics, $medialib, $user_level);
        return $this->view->render();
    }

    public function remove($id)
    {
        $id = abs((int)$id);

        if ($id <= 0) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return;
        }

        $getPost = $this->postService->grabPost($id);
        if (!$getPost) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return;
        }

        try {
            $this->postService->setPostId($id);
            $this->postService->removePost();
            $_SESSION['status'] = "postDeleted";
            direct_page('index.php?load=posts&status=postDeleted', 200);
        } catch (\Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        }
    }

    // ─── Security ──────────────────────────────────────────────

    private function checkPostCsrf()
    {
        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . MESSAGE_BADREQUEST, true, 400);
            header('Status: 400 Bad Request');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    private function checkPostPayload()
    {
        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content',
            'post_date', 'image_id', 'catID', ...]) === false) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . ' 413 Payload Too Large', true, 413);
            header('Status: 413 Payload Too Large');
            header('Retry-After: 3600');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    // ─── Validation (DTO + Validators) ────────────────────────

    private function validatePostSubmission(...)
    {
        $dto = PostRequestDto::fromGlobals();
        $result = (new PostValidator())->validate($dto);
        // ... merge errors ...

        $uploadedFile = UploadedFileDto::fromGlobals();
        if ($uploadedFile->isValid()) {
            $fileResult = (new FileUploadValidator())->validate($uploadedFile);
            // ... merge errors ...
        }

        if ($dto->isProtected()) {
            $pwdResult = (new ProtectedPostValidator())->validate($dto);
            // ... merge errors ...
        }
        return $checkError;
    }

    // ─── Rendering ────────────────────────────────────────────

    private function renderNewPostForm($errors, $formData, $topics, $medialib, $user_level)
    {
        // Sets view, pageTitle, formAction, topics, medialibs,
        // postStatus/commentStatus/visibility/locale dropdowns, csrfToken
    }

    private function renderEditPostForm($errors, $data_post, $getPost, $topics, $medialib, $user_level)
    {
        // Same as renderNewPostForm but with existing post data
        // Handles protected post decryption via decrypt_post_admin()
    }

    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'posts', $viewName);
    }
}
```

### Key Methods in PostController

| Method | Purpose |
|-------|---------|
| `listItems()` | Display all posts list |
| `insert()` | Display form / create new post (delegates to `$this->appService->createPost()`) |
| `update($id)` | Display form / update existing post (delegates to `$this->appService->updatePost()`) |
| `remove($id)` | Delete post |
| `checkPostCsrf()` | Validate CSRF token |
| `checkPostPayload()` / `checkPostUpdatePayload()` | Whitelist POST keys |
| `validatePostSubmission()` / `validatePostUpdate()` | DTO + Validator-based validation |
| `renderNewPostForm()` / `renderEditPostForm()` | View rendering helpers |
| `setView($name)` | Set view to render |

### Controller Flow (Post Refactoring)

```
User Request
    |
    v
PostController::{method}()
    |
    +-> checkPostCsrf()              - CSRF token validation
    +-> checkPostPayload()           - Form key whitelist
    +-> validatePostSubmission()     - DTO + PostValidator
    |     +-> PostRequestDto::fromGlobals()
    |     +-> (new PostValidator())->validate($dto)
    |     +-> (new FileUploadValidator())->validate($uploadedFile)
    |     +-> (new ProtectedPostValidator())->validate($dto)
    +-> PostApplicationService       - All business logic
    |     +-> createPost() / updatePost()
    |           +-> File upload (upload_photo)
    |           +-> PostService setters
    |           +-> Password encryption (setProtected / setPassPhrase)
    |           +-> Content encryption (protected posts)
    |           +-> PostDao createPost / updatePost
    +-> Set session status
    +-> Redirect or render view
```

> **Key insight**: The controller's `insert()` and `update()` methods are now ~40 and ~70 lines respectively (down from ~80+ each in the original monolithic controller). All post preparation logic - media upload, encryption, slug generation, tag/headline processing - moved verbatim into `PostApplicationService`. The PostApplicationService itself is the orchestration layer between the controller and the domain services (PostService, TopicDao, MediaDao).

### BaseApp Class

Controllers extend `BaseApp` which provides:

```php
// lib/core/BaseApp.php
class BaseApp
{
    protected $pageTitle;
    protected $formAction;
    
    protected function setView($viewName);  // Set view
    protected function setPageTitle($title); // Set page title
    protected function setFormAction($action); // Set form action
    protected function getPageTitle();      // Get page title
    protected function getFormAction();      // Get form action
}
```

### Security in Controllers

| Feature | Implementation |
|---------|----------------|
| **CSRF Protection** | `csrf_check_token()` validates token |
| **Form Validation** | `check_form_request()` validates required fields |
| **File Upload** | `check_file_name()`, `check_mime_type()`, `check_file_extension()` |
| **Password Strength** | `check_common_password()`, `check_pwd_strength()` |
| **Input Sanitization** | `Sanitize::strictSanitizer()`, `Sanitize::mildSanitizer()` |
| **Dropdown Validation** | `sanitize_selection_box()` |

### Other Controllers

| Controller | File | Purpose |
|------------|------|---------|
| `ReplyController` | `lib/controller/ReplyController.php` | Nested comment replies |
| `DownloadController` | `lib/controller/DownloadController.php` | Frontend download display |
| `DownloadAdminController` | `lib/controller/DownloadAdminController.php` | Admin download management |
| `ExportController` | `lib/controller/ExportController.php` | Content export (WordPress, Ghost, etc.) |
| `ImportController` | `lib/controller/ImportController.php` | Content import |
| `LanguageController` | `lib/controller/LanguageController.php` | Language management |
| `TranslationController` | `lib/controller/TranslationController.php` | Translation management |
| `UserController` | `lib/controller/UserController.php` | User management |
| `CommentController` | `lib/controller/CommentController.php` | Comment handling |
| `TopicController` | `lib/controller/TopicController.php` | Category management |
| `MediaController` | `lib/controller/MediaController.php` | Media library |
| `PageController` | `lib/controller/PageController.php` | Static pages |
| `MenuController` | `lib/controller/MenuController.php` | Navigation |
| `PluginController` | `lib/controller/PluginController.php` | Plugin management |
| `ThemeController` | `lib/controller/ThemeController.php` | Theme management |
| `ConfigurationController` | `lib/controller/ConfigurationController.php` | Settings |

---

## 10. Working with Models

### Model Guidelines

| Principle | Description |
|-----------|-------------|
| **Extends BaseModel** | Models extend `BaseModel` for database connectivity |
| **Data Retrieval** | Models contain SQL queries for fetching data |
| **View Preparation** | Models prepare data for views (transformations, joins, aggregations) |
| **No Business Logic** | Keep business logic in Services, not Models |

### BaseModel Class

All Models extend `BaseModel`, which provides database access and utility methods:

```php
// lib/model/BaseModel.php
class BaseModel
{
    protected $dbc;        // Database connection (PDO)
    protected $pagination; // Pagination HTML links
    protected $tableName;  // Current table name

    // Database methods
    protected function setSQL($sql);           // Set SQL query
    protected function findAll($data = []);       // Execute query, return all rows
    protected function findRow($data = [], $fetchMode = null); // Execute query, return single row
    
    // Table prefix handling
    protected function table($table);           // Apply table prefix
    
    // Additional utilities
    public function getSql();                  // Get current SQL
    public function getPaginator();             // Get pagination HTML
}
```

### PostModel Implementation

The actual `PostModel` class at `lib/model/PostModel.php` extends `BaseModel` and provides data retrieval methods:

```php
// lib/model/PostModel.php
namespace Scriptlog\Model;

use Scriptlog\Core\BaseModel;

defined('SCRIPTLOG') || die("Direct access not permitted");

class PostModel extends BaseModel
{
    private $linkPosts; // Paginator instance

    /**
     * Get post feeds for social sharing
     * @param int $limit Number of posts to retrieve
     * @return array|null Posts data
     */
    public function getPostFeeds($limit)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author,
                  p.post_date, p.post_modified, p.post_title,
                  p.post_slug, p.post_content, p.post_type,
                  p.post_status, p.post_tags, 
                  p.post_sticky, u.user_fullname, u.user_login
            FROM tbl_posts AS p
            INNER JOIN tbl_users AS u ON p.post_author = u.ID
            WHERE p.post_type = 'blog' AND p.post_status = 'publish'
            AND p.post_visibility = 'public'
            ORDER BY p.ID DESC LIMIT :limit";

        $this->setSQL($sql);
        $feeds = $this->findAll([':limit' => $limit]);
        return (empty($feeds)) ?: $feeds;
    }

    /**
     * Get latest published posts for homepage
     * @param int $limit Number of posts
     * @return array|null Posts with media, author, comments count, topics
     */
    public function getLatestPosts($limit)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author,
            p.post_date AS created_at, p.post_modified AS modified_at,
            p.post_title, p.post_slug, p.post_content, p.post_summary,
            p.post_keyword, p.post_status, p.post_tags,
            p.post_type, p.comment_status,
            m.media_filename, m.media_caption, m.media_access,
            u.user_fullname, u.user_login,
            (SELECT COUNT(c.ID) FROM " . $this->table('tbl_comments') . " c 
             WHERE c.comment_post_id = p.ID AND c.comment_status = 'approved') AS total_comments,
            (SELECT GROUP_CONCAT(CONCAT(t.ID, ':', t.topic_title, ':', t.topic_slug) SEPARATOR '|') 
             FROM " . $this->table('tbl_post_topic') . " pt 
             JOIN " . $this->table('tbl_topics') . " t ON pt.topic_id = t.ID 
             WHERE pt.post_id = p.ID AND t.topic_status = 'Y') AS topics_data
            FROM " . $this->table('tbl_posts') . " AS p
            LEFT JOIN " . $this->table('tbl_media') . " AS m ON p.media_id = m.ID
                AND m.media_target = 'blog'
                AND m.media_access = 'public'
                AND m.media_status = '1'
            INNER JOIN " . $this->table('tbl_users') . " AS u ON p.post_author = u.ID
            WHERE p.post_status = 'publish'
            AND p.post_type = 'blog'
            AND u.user_banned = '0'
            ORDER BY p.post_date DESC LIMIT :limit";

        $this->setSQL($sql);
        $latestPosts = $this->findAll([':limit' => $limit]);
        return (empty($latestPosts)) ?: $latestPosts;
    }

    /**
     * Get all published blog posts with pagination
     * @param object $sanitize Sanitize object for pagination
     * @param Paginator $perPage Paginator instance
     * @return array posts and pagination links
     */
    public function getAllBlogPosts($sanitize, Paginator $perPage)
    {
        $entries = [];

        $this->linkPosts = $perPage;
        
        // Set total records for pagination
        $stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_posts WHERE post_type = 'blog'");
        $this->linkPosts->set_total($stmt->rowCount());

        $sql = "SELECT p.ID, p.media_id, p.post_author,
            p.post_date AS created_at, p.post_modified AS modified_at,
            p.post_title, p.post_slug, p.post_content, p.post_summary,
            p.post_keyword, p.post_type, p.post_status, p.post_sticky,
            u.user_login, u.user_fullname,
            m.media_filename, m.media_caption,
            (SELECT COUNT(c.ID) FROM " . $this->table('tbl_comments') . " c 
             WHERE c.comment_post_id = p.ID AND c.comment_status = 'approved') AS total_comments,
            (SELECT GROUP_CONCAT(CONCAT(t.ID, ':', t.topic_title, ':', t.topic_slug) SEPARATOR '|') 
             FROM " . $this->table('tbl_post_topic') . " pt 
             JOIN " . $this->table('tbl_topics') . " t ON pt.topic_id = t.ID 
             WHERE pt.post_id = p.ID AND t.topic_status = 'Y') AS topics_data
            FROM " . $this->table('tbl_posts') . " AS p
            INNER JOIN " . $this->table('tbl_users') . " AS u ON p.post_author = u.ID
            LEFT JOIN " . $this->table('tbl_media') . " AS m ON p.media_id = m.ID
                AND m.media_target = 'blog'
                AND m.media_status = '1'
            WHERE p.post_type = 'blog'
            AND p.post_status = 'publish'
            AND u.user_banned = '0'
            ORDER BY p.ID DESC " . $this->linkPosts->get_limit($sanitize);
        
        $this->setSQL($sql);
        $entries = $this->findAll([]);
        $this->pagination = $this->linkPosts->page_links($sanitize);

        return (empty($entries)) ?: ['blogPosts' => $entries, 'paginationLink' => $this->pagination];
    }

    /**
     * Get single post by ID
     * @param int $id Post ID
     * @return array|null Post with media and author
     */
    public function getPostById($id)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, 
          p.post_title, p.post_slug, p.post_content, p.post_summary, p.post_keyword, 
          p.post_status, p.post_sticky, p.post_type, p.post_visibility, p.post_password, 
          p.comment_status AS comment_permit, m.media_filename, m.media_caption, m.media_target, 
          m.media_access, m.media_status, u.user_login, u.user_fullname
          FROM tbl_posts p
          LEFT JOIN tbl_media m ON p.media_id = m.ID AND m.media_target = 'blog' AND m.media_access = 'public' AND m.media_status = '1'
          LEFT JOIN tbl_users u ON p.post_author = u.ID
          WHERE p.ID = :ID 
          AND p.post_status = 'publish'
          AND p.post_type = 'blog'";

        $sanitizeid = Sanitize::severeSanitizer($id);
        $this->setSQL($sql);
        $item = $this->findRow([':ID' => $sanitizeid]);
        return (empty($item)) ?: $item;
    }

    /**
     * Get single post by slug
     * @param string $slug Post slug
     * @return array|null Post with media and author
     */
    public function getPostBySlug($slug, $fetchMode = null)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author,
                 p.post_date, p.post_modified, p.post_title,
                 p.post_slug, p.post_content, p.post_summary,
                 p.post_keyword, p.post_status, p.post_sticky, 
                 p.post_type, p.comment_status, 
                 m.media_filename, m.media_caption, m.media_target, m.media_access,
                 u.user_login, u.user_fullname
          FROM tbl_posts AS p
          INNER JOIN tbl_users AS u ON p.post_author = u.ID
          LEFT JOIN tbl_media AS m ON p.media_id = m.ID
              AND m.media_target = 'blog'
              AND m.media_access = 'public'
              AND m.media_status = '1'
          WHERE p.post_slug = :slug 
          AND p.post_status = 'publish'
          AND p.post_type = 'blog'";

        $slug_sanitized = Sanitize::severeSanitizer($slug);
        $this->setSQL($sql);
        $postBySlug = is_null($fetchMode) ? $this->findRow([':ID' => $slug_sanitized]) : $this->findRow([':ID' => $slug_sanitized], $fetchMode);
        return (empty($postBySlug)) ?: $postBySlug;
    }

    /**
     * Get random headline posts
     * @return array Posts marked as headlines
     */
    public function getRandomHeadlines()
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author,
             p.post_date, p.post_modified, p.post_title,
             p.post_slug, p.post_content, p.post_summary,
             p.post_keyword, p.post_sticky, p.post_type, p.post_status, 
             p.post_tags, u.user_login, u.user_fullname,
             m.media_filename, m.media_caption, m.media_type, m.media_target, m.media_access
             FROM tbl_posts AS p
             INNER JOIN (SELECT ID FROM tbl_posts ORDER BY RAND() LIMIT 5) AS p2 ON p.ID = p2.ID 
             INNER JOIN tbl_users AS u ON p.post_author = u.ID
             LEFT JOIN tbl_media AS m ON p.media_id = m.ID
                 AND m.media_target = 'blog' 
             WHERE p.post_type = 'blog'
             AND p.post_status = 'publish' 
             AND p.post_headlines = '1'";

        $this->setSQL($sql);
        $headlines = $this->findAll([]);
        return (empty($headlines)) ?: $headlines;
    }

    /**
     * Get related posts using full-text search
     * @param string $post_title Search term
     * @return array Related posts
     */
    public function getRelatedPosts($post_title)
    {
        $sql = "SELECT ID, media_id, post_author, post_date, post_modified,
                 post_title, post_slug, post_content, 
                 MATCH(post_title, post_content, post_tags)
                 AGAINST(?) AS score
          FROM tbl_posts 
          WHERE MATCH(post_title, post_content) AGAINST(?)
          ORDER BY score ASC LIMIT 3";

        $this->setSQL($sql);
        $relatedPosts = $this->findRow([$post_title]);
        return (empty($relatedPosts)) ?: $relatedPosts;
    }

    /**
     * Get random posts for homepage
     * @param int $start Start position
     * @param int $end End position
     * @return array Posts for homepage display
     */
    public function getRandomPosts($start, $end)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                   p.post_title, p.post_slug, p.post_content,
                   m.media_filename, m.media_caption, u.user_login, u.user_fullname,
                   (SELECT COUNT(c.ID) FROM " . $this->table('tbl_comments') . " c WHERE c.comment_post_id = p.ID AND c.comment_status = 'approved') AS total_comments,
                   (SELECT GROUP_CONCAT(CONCAT(t.ID, ':', t.topic_title, ':', t.topic_slug) SEPARATOR '|') 
                    FROM " . $this->table('tbl_post_topic') . " pt 
                    JOIN " . $this->table('tbl_topics') . " t ON pt.topic_id = t.ID 
                    WHERE pt.post_id = p.ID AND t.topic_status = 'Y') AS topics_data
          FROM " . $this->table('tbl_posts') . " AS p
          INNER JOIN (SELECT ID FROM " . $this->table('tbl_posts') . " ORDER BY RAND() LIMIT 3) AS p2 ON p.ID = p2.ID
          INNER JOIN " . $this->table('tbl_users') . " AS u ON p.post_author = u.ID
          LEFT JOIN " . $this->table('tbl_media') . " AS m ON p.media_id = m.ID
              AND m.media_target = 'blog'
          WHERE p.post_type = 'blog'
          AND p.post_status = 'publish'
          LIMIT :position, :end";

        $this->setSQL($sql);

        $data = array(':position' => $start, ':end' => $end);

        $randomPosts = $this->findAll($data);

        return (empty($randomPosts)) ?: $randomPosts;
    }

    /**
     * Get random posts for sidebar
     * @param int $limit Number of posts
     * @return array Posts for sidebar display
     */
    public function getPostsOnSidebar($limit)
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title,
               p.post_slug, p.post_content, p.post_summary,
               p.post_keyword, p.post_sticky, p.post_type, p.post_status, 
               u.user_login, u.user_fullname
            FROM tbl_posts AS p
            INNER JOIN tbl_users AS u ON p.post_author = u.ID
            WHERE p.post_type = 'blog' 
            AND p.post_status = 'publish'
            ORDER BY p.post_date DESC LIMIT :limit";

        $this->setSQL($sql);
        $sidebar_posts = $this->findAll([':limit' => $limit]);
        return (empty($sidebar_posts)) ?: ['sidebarPosts' => $sidebar_posts];
    }
}
```

### Key Methods in PostModel

| Method | Purpose | Returns |
|--------|---------|---------|
| `getPostFeeds($limit)` | Retrieve posts for social sharing | Array of published posts |
| `getLatestPosts($limit)` | Get latest posts for homepage | Array with media, author, comments, topics |
| `getAllBlogPosts($sanitize, $perPage)` | Paginated blog posts | Array with posts and pagination |
| `getPostById($id)` | Get single post by ID | Post array or null |
| `getPostBySlug($slug)` | Get single post by slug | Post array or null |
| `getPostByAuthor($author)` | Get posts by author | Post array or null |
| `getRandomHeadlines()` | Get random headline posts | Array of headlines |
| `getRelatedPosts($title)` | Get related posts (full-text) | Array of related posts |
| `getRandomPosts($start, $end)` | Get random posts | Array of random posts |
| `getPostsOnSidebar($limit)` | Get posts for sidebar | Array with sidebarPosts key |

### Database Columns Used

The `PostModel` references these `tbl_posts` columns:

| Column | Usage |
|--------|-------|
| `ID` | Post primary key |
| `media_id` | Featured image |
| `post_author` | User foreign key |
| `post_date` | Creation timestamp |
| `post_modified` | Last modification |
| `post_title` | Post title |
| `post_slug` | URL slug |
| `post_content` | Full content |
| `post_summary` | Short summary |
| `post_keyword` | SEO keywords |
| `post_status` | publish/draft |
| `post_visibility` | public/private/protected |
| `post_password` | Password hash (when protected) |
| `post_tags` | Comma-separated tags |
| `post_type` | blog/page |
| `post_sticky` | Sticky post flag |
| `comment_status` | open/closed |
| `post_headlines` | Headline/slideshow flag |
| `post_locale` | Language code |

### Other Models

| Model | File | Purpose |
|-------|------|---------|
| `FrontContentModel` | `lib/model/FrontContentModel.php` | Frontend-specific queries |
| `TopicModel` | `lib/model/TopicModel.php` | Category/topic queries |
| `TagModel` | `lib/model/TagModel.php` | Tag-based queries |
| `PageModel` | `lib/model/PageModel.php` | Static pages |
| `CommentModel` | `lib/model/CommentModel.php` | Comments |
| `GalleryModel` | `lib/model/GalleryModel.php` | Media galleries |
| `ArchivesModel` | `lib/model/ArchivesModel.php` | Archive queries |
| `DownloadModel` | `lib/model/DownloadModel.php` | Download tracking |

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
├── small/          # Thumbnail images (640x450)
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
    string $decoding = 'auto',
    string $loading = 'auto' // 'lazy' for gallery images
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

> **Image troubleshooting**: See `dev-docs/TROUBLESHOOTING.md` - [Image/Media Issues](TROUBLESHOOTING.md#imagemedia-issues).

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

> **ℹ️ Comprehensive Theme Developer Guide:** For a complete, standalone guide covering architecture, directory structure, template hierarchy, functions reference, navigation/i18n URL compatibility, assets, images, i18n, security, step-by-step creation, testing, and troubleshooting, see [THEME_DEVELOPER_GUIDE.md](THEME_DEVELOPER_GUIDE.md). This section is a concise reference summary.

### Required Theme Files

```
public/themes/[theme-name]/
├── theme.ini              # Theme metadata (REQUIRED)
├── functions.php          # Theme functions & template tags (REQUIRED)
├── header.php            # HTML head, navigation, CSS assets
├── footer.php            # Scripts, footer content, cookie consent
├── home.php              # Homepage template
├── single.php            # Single post view (handles protected posts)
├── page.php              # Static page view
├── category.php          # Category archive
├── tag.php               # Tag archive
├── archive.php           # Monthly archive
├── archives.php          # Archive index (all months)
├── blog.php              # Blog listing page
├── sidebar.php           # Sidebar widgets
├── 404.php               # 404 error page
├── privacy.php           # Privacy policy page
├── cookie-consent.php    # GDPR cookie consent banner
├── render-comments.php   # Comments rendering function
├── download.php          # Download page template
├── download_file.php     # File download handler
├── index.php             # Entry point (usually empty)
└── lang/                 # Translation files (7 languages)
    ├── en.json           # English (always required)
    ├── ar.json, zh.json, fr.json, ru.json, es.json, id.json
```

### theme.ini Format

```ini
[info]
theme_name = "My Custom Theme"
theme_designer = "Your Name"
theme_description = "Description of the theme's features"
theme_directory = "my-custom-theme"
```

### Critical: Template Loading Pattern

**NEVER include `call_theme_header()` or `call_theme_footer()` in template files.** The core system (`HandleRequest.php`) loads them automatically in sequence: header → content → footer.

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
// NO call_theme_header() - system handles it
// Template content starts directly:
?>
<div class="container">
    <!-- Page content -->
</div>
<?php
// NO call_theme_footer() - system handles it
```

This prevents duplicate HTML, "headers already sent" errors, and ensures proper 404 handling.

### Template Resolution

Templates are resolved by the `ThemeRenderer` (`lib/core/ThemeRenderer.php`), which loads `header.php`, then the template matching the route key, then `footer.php`. Each route maps directly to `{routeKey}.php`; there is no `index.php` fallback chain.

| Route | Template |
|-------|----------|
| `/` | `home.php` |
| `/post/{id}/{slug}` | `single.php` |
| `/page/{slug}` | `page.php` |
| `/category/{slug}` | `category.php` |
| `/tag/{tag}` | `tag.php` |
| `/archive/{mm}/{yyyy}` | `archive.php` |
| `/archives` | `archives.php` |
| `/blog*` | `blog.php` |
| 404 | `404.php` |

### Asset Locations

| Type | Location |
|------|----------|
| Main CSS | `assets/css/style.sea.min.css` |
| Custom/Cookie/Comment/Privacy/404/RTL CSS | `assets/css/*.min.css` |
| Navigation CSS | `assets/css/sina-nav.min.css` |
| Animation CSS | `assets/css/animate.min.css` |
| Frontend JS | `assets/js/front.min.js` |
| Feature JS (search, unlock, comments, cookie) | `assets/js/*.min.js` |
| Vendor | `assets/vendor/{bootstrap,jquery,font-awesome,@fancyapps/fancybox,popper.js,jquery.cookie}/` |

### Template Compliance

- All templates start with `defined('SCRIPTLOG') \|\| die('Direct access not permitted')`
- All dynamic output is escaped with `theme_escape_html()` (single escaping boundary); `htmLawed()` is used for content sanitization
- All forms include CSRF token via `block_csrf()`
- Protected posts use `$post_visibility === 'protected'` check (not `'password-protected'`)
- Images use `invoke_frontimg()`, `invoke_responsive_image()`, or `invoke_hero_image()`
- Theme registration: activate via **Appearance → Templates** in admin panel

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
| **Production** | `https://blogware.site/api/v1` |
| **Development** | `http://localhost:8080/api/v1` |

> **NOTE:** The complete OpenAPI 3.0 specification is available at `/docs/API_OPENAPI.json` and `/docs/API_OPENAPI.yaml`.

### API Version: v1

**Latest Enhancements (v1.1.0):**
- **Rate Limiting**: File-based sliding window rate limiter with per-client tracking
- **HATEOAS**: RFC 5988 Web Linking support - all responses include `_links` for discoverable navigation

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
1. **API Key** (`X-API-Key` header) - if provided
2. **Bearer Token** (`Authorization` header) - if provided
3. **IP Address** (`REMOTE_ADDR`) - fallback

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
Host: blogware.site
X-API-Key: your-api-key-here
```

#### Bearer Token Authentication

```
GET /api/v1/posts HTTP/1.1
Host: blogware.site
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
| **Total Tests** | 2,269 |
| **Test Files** | 167 (`find tests -name "*Test.php"`) |
| **Assertions** | 8,074 |
| **Skipped / Risky** | 15 skipped, 60 risky |
| **PHPUnit Version** | 9.6.35 |
| **Target Coverage** | 40% |
| **Current Coverage** | ~38% |

### Test Coverage Plan

The test coverage plan is organized into phases:

#### Phase Status

| Phase | Priority | Status | Tests |
|-------|----------|--------|-------|
| Phase 1: DAO Integration | HIGH | ✅ Complete | 92 |
| Phase 2: Service Layer | HIGH | ✅ Complete | 148 |
| Phase 3: Core Classes | MEDIUM | ✅ Complete | 65 |
| Phase 4: Controllers | MEDIUM | ✅ Complete | 34 |
| Phase 5: Utilities | LOW | ✅ Complete | 68 |
| Password Protected Posts | HIGH | ✅ Complete | 42 |

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
| `tests/unit/ProtectedPostTest.php` | 11 | Core `protect_post()` / encryption / password hash & verify |
| `tests/unit/ProtectedPostRateLimitTest.php` | 20 | Rate limiting & password strength |
| `tests/unit/PostControllerProtectedPostTest.php` | 11 | Controller flow & validation |

**Total: 42 tests**

| Test Category | Tests |
|--------------|-------|
| Rate Limiting Logic | 8 (5-attempt limit, old-attempt expiration, per-IP/per-post) |
| Password Strength | 7 (length, uppercase, lowercase, number, special char) |
| Encryption/Decryption + `protect_post()` | 7 |
| Session Storage | 6 |
| Password Hash & Verify | 6 |
| Functions Existence | 3 |
| Visibility Validation | 3 |
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

See `dev-docs/TROUBLESHOOTING.md` for documented issues and solutions covering installation, database, post/content, image/media, auth/session, i18n/translation, API, navigation/URL, theme, and server/config issues.

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
APP_VERSION        // '1.8.1'
APP_DEVELOPMENT    // true or false
```

## Key Classes

All classes live under `Scriptlog\*` namespaces (e.g., `Scriptlog\Core\Bootstrap`, `Scriptlog\Dao\PostDao`).

| Category | Namespace | Classes |
|----------|-----------|---------|
| **Core** | `Scriptlog\Core` | Bootstrap, Dispatcher, DbFactory, Authentication, SessionMaker, Registry, FormValidator, Sanitize, View, Dao, Db, SearchFinder, CSRFGuard, BaseApp, BaseModel |
| **DAO** | `Scriptlog\Dao` | PostDao, UserDao, CommentDao, ReplyDao, TopicDao, PostTopicDao, MediaDao, PageDao, MenuDao, PluginDao, ThemeDao, ConfigurationDao, ConsentDao, DataRequestDao, PrivacyLogDao, PrivacyPolicyDao, LanguageDao, TranslationDao, UserTokenDao |
| **Service** | `Scriptlog\Service` | PostService, PostApplicationService, ProtectedPostService, ScheduledPostService, UserService, CommentService, ReplyService, TopicService, MediaService, PageService, MenuService, PluginService, ThemeService, ConfigurationService, ConsentService, DataRequestService, LanguageService, TranslationService, DownloadService, ExportService, MigrationService, FrontService, NotificationService |
| **Controller** | `Scriptlog\Controller` | PostController, UserController, CommentController, ReplyController, TopicController, MediaController, PageController, MenuController, PluginController, ThemeController, ConfigurationController, DownloadController, DownloadAdminController, ExportController, ImportController, LanguageController, LocaleController, SearchController, TranslationController, ApiController |
| **API Controller** | `Scriptlog\Controller\Api` | ApiController (info), PostsApiController, CategoriesApiController, CommentsApiController, ArchivesApiController, SearchApiController, GdprApiController, LanguagesApiController, TranslationsApiController, MediaApiController, ProtectedPostApiController, QueryApiController |
| **Model** | `Scriptlog\Model` | PostModel, FrontContentModel, TopicModel, TagModel, PageModel, CommentModel, GalleryModel, ArchivesModel, DownloadModel |
| **Handler** | `Scriptlog\Handler` | HandlerRegistry, FrontRequestHandler, AdminActionRegistry, AdminActionCommand, PostHandler, PageHandler, CategoryHandler, TagHandler, ArchiveHandler, PrivacyHandler, DownloadHandler, BlogHandler, HomeHandler |

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

// Utility
get_ip_address();
app_url();
app_info();
theme_identifier();
invoke_frontimg($media_filename, $image_thumb = true);
make_date($timestamp);             // Format date for display (frontend only, e.g. "July 26, 2026")
                                    // ⚠ NOT for admin form <input> values - use raw Y-m-d H:i:s instead
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
| `symfony/mailer` | ^5.4 | Email delivery | `lib/service/NotificationService.php` |
| `vlucas/phpdotenv` | ^5.6 | .env file loading | `install/include/setup.php` |
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
|              |             - Default (en)                     |
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
CREATE TABLE IF NOT EXISTS {$prefix}tbl_languages (
    ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    lang_code VARCHAR(10) NOT NULL,
    lang_name VARCHAR(50) NOT NULL,
    lang_native VARCHAR(50) NOT NULL,
    lang_locale VARCHAR(10) DEFAULT NULL,
    lang_direction ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    lang_sort INT(11) NOT NULL DEFAULT 0,
    lang_is_default TINYINT(1) NOT NULL DEFAULT 0,
    lang_is_active TINYINT(1) NOT NULL DEFAULT 1,
    lang_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY lang_code (lang_code)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

**tbl_translations** - Translation strings

```sql
CREATE TABLE IF NOT EXISTS {$prefix}tbl_translations (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    lang_id INT(11) UNSIGNED NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    translation_context VARCHAR(100) DEFAULT NULL,
    translation_plurals VARCHAR(255) DEFAULT NULL,
    is_html TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY lang_key (lang_id, translation_key),
    KEY lang_id (lang_id),
    KEY translation_key (translation_key(191))
) Engine=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Language Detection Priority

1. **Session** - `scriptlog_locale` (set when the user switches language)
2. **Cookie** - `scriptlog_locale` cookie set by the language switcher
3. **Accept-Language Header** - Browser's language preference
4. **Default** - `en` (configurable)

### URL Routing for Languages

There are no URL-prefix routes like `/ar/...` in the routing table. Language switching uses two mechanisms:

1. **`/locale` route** - registered in `Bootstrap::defineRoutingRules()`
2. **`?switch-lang={code}` query parameter** - handled in `lib/main.php`; it stores the locale in the session (`scriptlog_locale`) and a `scriptlog_locale` cookie, then redirects back with `switch-lang` removed from the URL

Valid locales: `en`, `ar`, `zh`, `fr`, `ru`, `es`, `id`.

### Translation Functions

```php
// Frontend: t() - defined in public/themes/blog/functions-i18n.php
t('key');                  // Returns translated string for current locale
t('form.save');            // Dot-notation keys; falls back to en, then to the key itself
t('welcome', ['name' => 'Sam']); // Placeholders: %name% replaced in the translated string

// Admin panel: hybrid DB + hardcoded fallback
admin_translate('key');    // Returns translated string
admin_t('key');            // Alias for admin_translate()
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
2. **Add Translations**: Insert into `tbl_translations` with `lang_id` (FK to `tbl_languages.ID`) and key
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

> **i18n troubleshooting**: See `dev-docs/TROUBLESHOOTING.md` - [i18n/Translation Issues](TROUBLESHOOTING.md#i18ntranslation-issues).

### Adding Content i18n Support

To add locale support to a new content type:

1. **Database**: Add `content_locale` column to table
2. **Dao**: Add `post_locale`-style locale column to the select/insert/update queries
3. **Service**: Add `setContentLocale()` method
4. **Controller**: Add locale filters and setters
5. **Admin UI**: Add locale dropdown to edit form via the `*_locale_dropdown()` utility (e.g. `post_locale_dropdown()` from `lib/utility/`) - the DAO-level `dropDown*()` helpers were removed in favor of utility functions

### Populating Languages and Translations

The system includes:
- 7 languages (en, ar, zh, fr, ru, es, id)
- 174 translation keys with 1,218 total translations (174 keys x 7 languages)
- Translation editor in admin panel (Settings → Translations)
- Translation cache in `public/files/cache/translations/`

Use the admin panel (Settings → Languages and Settings → Translations) to manage languages and translations.

### Configuration

Default language settings are detected at runtime. The `LocaleDetector` (`lib/core/LocaleDetector.php`) defaults to `'en'`:

```php
private $defaultLocale = 'en';
```

The 7 supported language codes are: `en`, `ar`, `zh`, `fr`, `ru`, `es`, `id`.

### Documentation

For comprehensive API documentation and testing, see:
- `I18N_ARCHITECTURE.md` - Full architecture documentation
- `I18N_API.md` - API reference
- `I18N_TESTING_GUIDE.md` - Testing guide

---

## Contributing

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

> **Export troubleshooting**: See `dev-docs/TROUBLESHOOTING.md` - [Server/Config Issues](TROUBLESHOOTING.md#serverconfig-issues) (XML Parse Error).

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

The search system provides three complementary paths for finding published content:

| Path | Route | Controller | Output |
|------|-------|-----------|--------|
| **Full page** | `/search?q=keyword` (permalinks ON) or `?q=keyword` on the app root (permalinks OFF) | `SearchController` (`/search`) / `HandleRequest::deliverQuerySearch()` (`?q=` OFF) | Renders `search.php` template |
| **AJAX inline** | `GET /api/v1/search?q=keyword` | `SearchApiController` | JSON response consumed by `search.js` |
| **HTMX inline** | `hx-get="/search"` on the search input (Valdur theme) | `SearchController` | HTML fragment (`partials/search-results.php`) swapped into `#search-suggestions` |

The full-page path is permalink-aware: with SEO-friendly permalinks enabled the Dispatcher routes the `/search` path to `SearchController`; with permalinks disabled the query-string router (`HandleRequest::deliverQueryString()`) dispatches the `q` key on the app root and `HandleRequest::deliverQuerySearch()` calls `SearchFinder::searchAll()` directly - it does **not** pass through `SearchController`. Use the `theme_search_url()` theme helper (`public/themes/blog/functions-post.php`) to build the search form action / JS `search_url` so it matches the active scheme instead of hard-coding `/search`.

All three paths delegate to the same `SearchFinder` engine.

### Key Files

| File | Purpose |
|------|---------|
| `lib/core/SearchFinder.php` | Core search engine - MySQL FULLTEXT `MATCH ... AGAINST` against `tbl_posts` |
| `lib/controller/SearchController.php` | Frontend controller for `/search` - rate-limited, `type`+`page` dispatch, full page or HTMX fragment |
| `lib/controller/api/SearchApiController.php` | REST API controller (`/api/v1/search`) |
| `public/themes/blog/search.php` | Search results page template |
| `public/themes/blog/sidebar.php` | Search form in sidebar |
| `public/themes/blog/assets/js/search.js` | AJAX autocomplete JS (300 ms debounce) |
| `public/themes/blog/lang/en.json` | Search i18n keys (`search.*`) |
| `lib/core/Dispatcher.php` | Routes `/search` to `SearchController` (permalinks ON) |
| `lib/core/HandleRequest.php` | `deliverQuerySearch()` - handles `?q=` on app root (permalinks OFF) via `SearchFinder::searchAll()` |
| `lib/core/Bootstrap.php` | Route definition: `'search' => "/search"` |
| `public/themes/blog/functions-post.php` | `theme_search_url()` - permalink-aware search URL builder |

### Architecture

```
                    ┌──────────────────────────────┐
                    │     User types/enters        │
                    │     search keyword           │
                    └──────────────┬───────────────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
              v                    v                    v
   ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐
   │ Blog theme           │ │ Valdur theme         │ │ Form submit (GET) /  │
   │ search.js (jQuery    │ │ HTMX hx-get="/search"│ │ direct URL           │
   │ AJAX, 300 ms debounce│ │ (300 ms keyup delay) │ │ /search?q= (ON) or   │
   │ GET /api/v1/search   │ │                      │ │ ?q= on root (OFF)    │
   └──────────┬───────────┘ └──────────┬───────────┘ └──────────┬───────────┘
              │                        │                        │
              v                        v                        v
   ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐
   │ SearchApiController  │ │ SearchController     │ │ HandleRequest::      │
   │ (API) → JSON         │ │ (rate-limited,       │ │ deliverQuerySearch() │
   │ index / posts /      │ │ type + page) →       │ │ (permalinks OFF only)│
   │ pages                │ │ HTMX fragment or     │ │ searchAll() direct - │
   │                      │ │ full page            │ │ no controller        │
   └──────────┬───────────┘ └──────────┬───────────┘ └──────────┬───────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
                                       v
                        ┌──────────────────────────────┐
                        │          SearchFinder        │
                        │  searchAll (all) / searchPost│
                        │  (posts) / searchPage (pages)│
                        │  FULLTEXT MATCH (post_title, │
                        │  post_content, post_tags)    │
                        │  AGAINST (? IN BOOLEAN MODE) │
                        └──────────────┬───────────────┘
                                       │
                        ┌──────────────┼──────────────┐
                        v                             v
                 ┌──────────────────────┐      ┌──────────────────────┐
                 │ JSON response        │      │ HTML output          │
                 │ ApiResponse::success │      │full page → search.php│
                 │ (AJAX + API path)    │      │ or HTMX fragment     │
                 │                      │      │ (partials/search-    │
                 │                      │      │ results.php)         │
                 └──────────────────────┘      └──────────────────────┘
```

> **Diagram notes (verified against the codebase):**
> - `search.js` always requests `type=all`; the API `posts`/`pages` endpoints (`SearchApiController@posts`/`@pages`) map to `searchPost`/`searchPage`.
> - Both `SearchController` and `SearchApiController` dispatch on `type` (`all`/`posts`/`pages`) - all three `SearchFinder` methods serve **both** JSON and HTML outputs; the output format depends on the entry controller, not the method.
> - The permalinks-OFF query-string path (`?q=` on app root) is handled by `HandleRequest::deliverQuerySearch()`, which calls `SearchFinder::searchAll()` directly - it bypasses `SearchController`.
> - HTMX requests (Valdur theme) return an HTML fragment (`partials/search-results.php`), not JSON.

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
  "data": {
    "keyword": "cicero",
    "type": "all",
    "total": 3,
    "results": [
      {
        "id": 1,
        "title": "Lorem ipsum dolor sit amet",
        "slug": "lorem-ipsum",
        "excerpt": "Lorem ipsum dolor sit amet, consectetur...",
        "type": "blog",
        "date": "2026-03-01 12:00:00",
        "url": "/post/1/lorem-ipsum"
      }
    ]
  }
}
```

### Search Page (Full Page Rendering)

When a user runs a search (form submit or a direct URL - `/search?q=keyword` with permalinks ON, `?q=keyword` on the app root with permalinks OFF), the `Dispatcher` routes to `SearchController`:

1. **`SearchController::search()`** reads `$_GET['q']` or `$_GET['keyword']` and `$_GET['type']`
2. Delegates to `SearchFinder::searchAll()`, `searchPost()`, or `searchPage()`
3. Sets `$GLOBALS['search_results']` and `$GLOBALS['search_keyword']`
4. Renders `search.php` via `ThemeRenderer`

The `search.php` template then reads from globals:

```php
$searchResults = $GLOBALS['search_results'] ?? [];
$searchKeyword = $GLOBALS['search_keyword'] ?? '';
$results = $searchResults['results'] ?? [];
$totalRows = $searchResults['totalRows'] ?? 0;
```

### Security Features

| Feature | Implementation |
|---------|---------------|
| **XSS Prevention** | Server-side sanitization via `SearchFinder::sanitizeKeyword()` + `theme_escape_html()` in template |
| **SQL Injection** | Uses PDO prepared statements |
| **CSRF Protection** | Hidden CSRF token in search form |
| **Input Validation** | Keyword length limits (min 2, max 100 characters), non-string rejection |

### URL Format Support

The search results support both SEO-friendly and query string URLs based on permalink settings:

**SEO-Friendly URLs (when permalinks enabled):**
- Posts: `/post/ID/slug`
- Pages: `/page/slug`

**Query String URLs (when permalinks disabled):**
- Posts: `?p=ID`
- Pages: `?pg=ID`

### Implementation Notes

- **Search engine**: Uses MySQL FULLTEXT `MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)` with `+` AND-semantics built by `SearchFinder::buildBooleanQuery()`. NOT `LIKE '%keyword%'` - the old LIKE implementation was replaced with FULLTEXT.
- **DB wrapper**: The `SearchFinder` class uses the custom `Db` class (PDO wrapper), NOT Medoo
- **DB access**: via `Registry::get('dbc')`
- **API auth**: Public endpoint (no authentication required)
- **API response fields**: id, title, slug, excerpt, type, date, url
- **HTMX support**: `SearchController` renders partial fragments when `is_htmx_request()` returns true (used by Valdur theme)

### Adding Search to Custom Themes

To add AJAX search + full results page to a custom theme:

1. **Create `search.php`** in your theme directory. Use `$GLOBALS['search_results']` and `$GLOBALS['search_keyword']`:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
$searchResults = $GLOBALS['search_results'] ?? [];
$searchKeyword = $GLOBALS['search_keyword'] ?? '';
$results = $searchResults['results'] ?? [];
$totalRows = $searchResults['totalRows'] ?? 0;
?>
<div class="container">
    <h1><?= t('search.title'); ?></h1>
    <!-- Iterate $results, display title/excerpt/type/date -->
</div>
```

2. **Include the sidebar search form** (typically in `sidebar.php`):

```php
<form action="<?= app_url(); ?>/search" method="get" class="search-form" id="ajax-search-form"
      role="search">
    <label for="search-keyword" class="sr-only"><?= t('sidebar.search.placeholder'); ?></label>
    <input type="search" id="search-keyword" name="q" placeholder="Search..."
           autocomplete="off" minlength="2">
    <button type="submit" aria-label="<?= t('sidebar.search.submit'); ?>">Search</button>
    <div id="search-results" class="search-results" aria-live="polite"></div>
    <?= block_csrf(); ?>
</form>
```

3. **Include the search JavaScript** in your `footer.php`:

```php
<script src="<?= theme_dir(); ?>assets/js/search.min.js" defer></script>
```

4. **Add CSS styles** for the search dropdown (see `custom.css` for reference).

5. **Add i18n keys** to your `lang/en.json`:

```json
{
    "search.title": "Search",
    "search.found_results": "Found %count% result(s) for \"%keyword%\"",
    "search.no_results": "No results found for \"%keyword%\"",
    "search.enter_keyword": "Please enter a search keyword to find content.",
    "search.try_different_keywords": "No results found. Please try different keywords.",
    "search.read_more": "Read More"
}
```

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
2. Admin saves → 
   - IF password changed: content encrypted with NEW passphrase
   - IF password NOT changed: content re-encrypted with EXISTING passphrase (fix: prevents transaction rollback)
3. Tags/categories/content now save correctly in both cases
```

### Database Schema

**tbl_posts columns used for protection:**

| Column | Purpose |
|--------|---------|
| `post_visibility` | Set to `protected` for protected posts |
| `post_password` | Bcrypt hash of the password |
| `passphrase` | SHA-256 hash used for encryption: `hash('sha256', app_key() . password)` |
| `post_content` | AES-encrypted content |

### Key Files

| File | Purpose |
|------|---------|
| `lib/controller/api/ProtectedPostApiController.php` | API controller with unlock/verify endpoints |
| `lib/utility/protected-post.php` | `decrypt_post()`, `decrypt_post_admin()`, rate limiting functions |
| `lib/utility/encrypt-decrypt.php` | `encrypt()`, `decrypt()` using AES-256-CBC |
| `lib/service/ProtectedPostService.php` | `resolve()`, `sanitizeContent()` - decrypts and sanitizes protected post content |
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
  "password": "YourPassword"
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

**Total: 42 tests across 3 files**

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `tests/unit/ProtectedPostTest.php` | 11 | Core `protect_post()` / encryption / password hash & verify |
| `tests/unit/ProtectedPostRateLimitTest.php` | 20 | Rate limiting & password strength |
| `tests/unit/PostControllerProtectedPostTest.php` | 11 | Controller flow & validation |

Run tests:
```bash
lib/vendor/bin/phpunit tests/unit/ProtectedPost*.php --bootstrap tests/bootstrap.php
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

> **Upload troubleshooting**: See `dev-docs/TROUBLESHOOTING.md` - [Summernote AJAX Upload](TROUBLESHOOTING.md#imagemedia-issues).

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
```
- media_filename: Unique filename
- media_type: 'image'
- media_target: 'blog'
- media_user: Username who uploaded
```

**tbl_mediameta** - Post linkage:
```
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


---

## License

This project is licensed under the MIT License.

---

*Last Updated: August 2026 | Version 1.8.1*
