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
|  7. Configuration saved to config.php                         |
+---------------------------------------------------------------+
```

**Step-by-Step Installation**

```bash
# Clone repository
git clone https://github.com/blogware/scriptlog.git
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

## 2. Architecture Overview

Blogware uses a **multi-layer architecture** designed for maintainability and scalability:

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
|   | Dispatcher         |  (lib/core/Dispatcher.php)           |
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

---

## 3. Directory Structure

```
blogware/public_html/
|
|-- index.php                    # Public front controller
|-- config.php                   # Application configuration
|
|-- admin/                       # Admin panel
|   |-- index.php               # Admin entry point
|   |-- login.php               # Login page
|   |-- posts.php               # Post management
|   |-- pages.php               # Page management
|   |-- topics.php              # Category management
|   |-- comments.php            # Comment management
|   |-- users.php               # User management
|   |-- menu.php                # Menu management
|   |-- templates.php           # Theme management
|   |-- plugins.php             # Plugin management
|   |-- medialib.php            # Media library
|   +-- ui/                     # Admin UI components
|
|-- api/                        # RESTful API
|   +-- index.php               # API entry point
|
|-- lib/                        # Core library
|   |-- main.php               # Application bootstrap
|   |-- common.php             # Constants and functions
|   |-- options.php            # PHP configuration
|   |-- Autoloader.php         # Class autoloader
|   |-- utility-loader.php     # Utility functions loader
|   |
|   +-- core/                  # Core classes (80+ files)
|       |-- Bootstrap.php      # Application initialization
|       |-- Dispatcher.php     # URL routing
|       |-- DbFactory.php     # PDO database connection
|       |-- Authentication.php # User authentication
|       |-- SessionMaker.php   # Custom session handler
|       |-- View.php          # View rendering
|       |-- ApiResponse.php   # API response handler
|       |-- ApiAuth.php       # API authentication
|       |-- ApiRouter.php     # API routing
|       +-- ...
|
|   +-- dao/                   # Data Access Objects
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
|   +-- vendor/               # Composer dependencies
|
|-- public/                   # Public web root
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
|-- docs/                     # Documentation
|   |-- DEVELOPER_GUIDE.md
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

Handles URL routing and dispatches requests to appropriate controllers.

```php
// Route patterns defined in Bootstrap
$rules = [
    'home'     => "/",
    'category' => "/category/(?'category'[\w\-]+)",
    'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
    'blog'     => "/blog([^/]*)",
    'page'     => "/page/(?'page'[^/]+)",
    'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
    'search'   => "(?'search'[\w\-]+)",
    'tag'      => "/tag/(?'tag'[\w\-]+)"
];
```

### DbFactory (`lib/core/DbFactory.php`)

Creates PDO database connections.

```php
$dbc = DbFactory::connect([
    'mysql:host=localhost;port=3306;dbname=blogwaredb',
    'username',
    'password'
]);
```

### Authentication (`lib/core/Authentication.php`)

Handles user authentication, login, logout, and session management.

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
if (!csrf_defender_verify($token)) {
    throw new \Exception("Invalid CSRF token");
}

// Get client IP
$ip = get_ip_address();
```

> **NOTE:** Always use utility functions for common operations. They are tested and follow security best practices.

---

## 12. Theming

### Theme Structure

```
public/themes/[theme-name]/
|-- functions.php         # Theme functions and hooks
|-- header.php            # Site header
|-- footer.php            # Site footer
|-- home.php              # Homepage template
|-- single.php            # Single post template
|-- page.php              # Page template
|-- category.php          # Category archive template
|-- archive.php           # Archive template
|-- tag.php               # Tag archive template
|-- comment.php           # Comment form/template
|-- sidebar.php           # Sidebar
|-- 404.php               # 404 error page
|+-- assets/              # CSS, JS, images
|   |-- css/
|   |-- js/
|   +-- img/
+-- theme.ini             # Theme metadata
```

### Theme Functions (functions.php)

```php
<?php
// Define theme support
function theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    
    // Register navigation menu
    register_nav_menus([
        'primary' => 'Primary Menu',
        'footer'  => 'Footer Menu'
    ]);
}
add_action('after_setup_theme', 'theme_setup');

// Enqueue scripts and styles
function theme_scripts()
{
    wp_enqueue_style('theme-style', get_theme_file_uri('/assets/css/style.css'));
    wp_enqueue_script('theme-script', get_theme_file_uri('/assets/js/script.js'), [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'theme_scripts');

// Template functions
function get_post_thumbnail($postId, $size = 'medium')
{
    $mediaDao = new MediaDao();
    $post = $postDao->findPostById($postId);
    
    if ($post && $post['media_id']) {
        return invoke_frontimg($post['media_filename'], $size);
    }
    
    return get_theme_file_uri('/assets/img/default.jpg');
}
```

### Theme Template Tags

```php
// In template files

// Header and footer
call_theme_header();
call_theme_footer();

// Post loop
if (have_posts()) {
    while (have_posts()) {
        the_post();
        
        // Display post data
        echo get_the_title();
        echo get_the_content();
        echo get_the_date();
        echo get_the_author();
        echo get_the_tags();
    }
}

// Pagination
front_paginator($wp_query);

// Comments
comments_template();
```

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

Blogware provides a RESTful API that allows external applications to interact with blog content. The API follows OpenAPI 3.0 specification and returns JSON responses.

| Environment | URL |
|-------------|-----|
| **Production** | `http://blogware.site/api/v1` |
| **Development** | `http://localhost/blogware/public_html/api/v1` |

> **NOTE:** The complete OpenAPI 3.0 specification is available at `/docs/API_OPENAPI.json` and `/docs/API_OPENAPI.yaml`.

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

### Test Suite Overview

| Metric | Value |
|--------|-------|
| **Total Tests** | 246 |
| **Assertions** | 290 |
| **PHPUnit Version** | 9.6.34 |

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage

# Run specific test file
vendor/bin/phpunit tests/EmailValidationTest.php

# Run tests matching pattern
vendor/bin/phpunit --filter "EmailValidation"
```

### Test Categories

| Category | Description |
|----------|-------------|
| **Unit Tests** | Utility function tests, class existence tests |
| **Integration Tests** | Database CRUD operations using `blogware_test` database |

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
| **DAO** | PostDao, UserDao, CommentDao, TopicDao, MediaDao, PageDao, MenuDao, PluginDao, ThemeDao, ConfigurationDao |
| **Service** | PostService, UserService, CommentService, TopicService, MediaService, PageService, MenuService, PluginService, ThemeService |

## Global Functions

```php
// Session
start_session_on_site($sessionMaker);
regenerate_session();

// Security
csrf_defender_verify($token);
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

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## License

This project is licensed under the MIT License.

---

*Last Updated: March 2026 | Version 1.0.0*
