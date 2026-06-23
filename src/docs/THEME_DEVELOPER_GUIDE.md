# Theme Developer Guide

**Project:** Blogware/Scriptlog CMS  
**Version:** 1.0.0 | **Last Updated:** June 2026

> **Audience:** Theme developers building custom themes from scratch. This guide is a superset of the theming section in [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) and covers the complete theme development lifecycle — from directory structure through testing and troubleshooting.

---

## Table of Contents

1. [Overview & Architecture Principles](#1-overview--architecture-principles)
2. [Theme Directory Structure](#2-theme-directory-structure)
3. [theme.ini Configuration](#3-themeini-configuration)
4. [Template Loading Pattern (CRITICAL)](#4-template-loading-pattern-critical)
5. [Template Hierarchy](#5-template-hierarchy)
6. [Complete Template Reference](#6-complete-template-reference)
7. [Theme Functions (functions.php) Complete Reference](#7-theme-functions-functionsphp-complete-reference)
8. [Navigation & i18n URL Compatibility](#8-navigation--i18n-url-compatibility)
9. [Asset Management](#9-asset-management)
10. [Image Handling System](#10-image-handling-system)
11. [i18n Integration for Themes](#11-i18n-integration-for-themes)
12. [Security Considerations](#12-security-considerations)
13. [Creating a Custom Theme — Step-by-Step](#13-creating-a-custom-theme--step-by-step)
14. [Theme Registration & Activation](#14-theme-registration--activation)
15. [Testing & Quality Assurance](#15-testing--quality-assurance)
16. [Troubleshooting Common Issues](#16-troubleshooting-common-issues)
17. [Files Reference](#17-files-reference)

---

## 1. Overview & Architecture Principles

### Architectural Qualities

The theming system is designed with these principles:

| Quality | How the Theme System Delivers |
|---------|-------------------------------|
| **Scalability** | Templates include only display logic — no database queries in templates. The `functions.php` file provides helper functions that call model/DAO layers, keeping templates thin and maintainable. Multiple themes can coexist. |
| **Security** | All output is escaped via `htmlout()`. Content is sanitized via `htmLawed()`. Forms include CSRF tokens via `block_csrf()`. All PHP files use `defined('SCRIPTLOG') || die()` guard. Password-protected posts use AES-256-CBC encryption with bcrypt hashing. |
| **Safety** | Template loading is handled by the core system (never manual `include`/`require` in templates). 404 handling happens in the Dispatcher before any output, preventing "headers already sent" errors. Fallback to default theme (blog) if active theme fails. |
| **Speed** | Asset minification via `tmp/minify.php`. Lazy loading images with `loading="lazy"`. Minified CSS/JS in production (`*.min.css`, `*.min.js`). Translation caching avoids repeated database lookups. |
| **Reliability** | `ThemeDao` includes fallback logic — if active theme is missing, defaults to `blog` theme. `theme_identifier()` in `lib/utility/theme-caller.php` safely resolves theme paths. All PHP files validated with `php -l` before deployment. |
| **UI/UX (Premium)** | Bootstrap 4 responsive grid. Mobile-first with breakpoints at 768px/1024px/1440px. ARIA labels on all interactive elements. Keyboard navigation. RTL support for Arabic. Fancybox lightbox for galleries. AJAX comment submission and search. Cookie consent banner (GDPR). |

### How the Theme System Works

```
Request → Dispatcher → validates content exists (404 check) → loads theme:
  1. call_theme_header()   → header.php (HTML head, nav, CSS)
  2. call_theme_content()  → home.php / single.php / page.php / etc.
  3. call_theme_footer()   → footer.php (scripts, close tags, cookie banner)
```

The theme directory is resolved by `lib/utility/theme-caller.php` → `theme_identifier()` which reads `tbl_themes` for the active theme. If no theme is active, it falls back to `blog`.

### Communication Flow: Theme ↔ Core

```
Theme Template (e.g., home.php)
    │
    ├── Calls functions.php helper functions
    │   ├── latest_posts()        → PostModel → DAO → DB
    │   ├── retrieve_page()       → PageModel  → DAO → DB
    │   ├── sidebar_topics()      → TopicModel → DAO → DB
    │   ├── retrieve_tags()       → PostModel  → DAO → DB
    │   └── front_navigation()    → MenuDao    → DB
    │
    ├── Uses security/utility functions
    │   ├── htmlout()            → escape output
    │   ├── htmLawed()           → sanitize content
    │   ├── block_csrf()         → CSRF token
    │   └── invoke_frontimg()    → display images
    │
    └── Uses i18n functions
        ├── t()                  → translate strings
        ├── locale_url()         → locale-prefixed URLs
        ├── get_locale()         → current locale
        └── is_rtl()             → RTL detection
```

---

## 2. Theme Directory Structure

### Required Files

A complete theme must include these files:

```
public/themes/[theme-name]/
├── theme.ini              # Theme metadata (REQUIRED)
├── functions.php          # Theme functions & template tags (REQUIRED)
├── header.php            # HTML head, navigation, CSS assets
├── footer.php            # Scripts, footer content, cookie consent
├── home.php              # Homepage template
├── single.php            # Single post view
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
├── index.php             # Entry point (usually empty)
├── render-comments.php   # Comments rendering function
├── download.php          # Download page template
├── download_file.php     # File download handler
└── lang/                 # Translation files
    ├── en.json           # English (always required)
    ├── ar.json           # Arabic
    ├── zh.json           # Chinese
    ├── fr.json           # French
    ├── ru.json           # Russian
    ├── es.json           # Spanish
    └── id.json           # Indonesian
```

### Asset Directory Structure

```
assets/
├── css/                  # Stylesheets
│   ├── style.sea.css     # Main theme styles (source)
│   ├── style.sea.min.css # Minified production version
│   ├── custom.css        # Custom overrides
│   ├── custom.min.css    # Minified custom CSS
│   ├── comment.css       # Comment section styling
│   ├── comment.min.css   # Minified comment CSS
│   ├── cookie-consent.css # Cookie banner styling
│   ├── cookie-consent.min.css # Minified cookie CSS
│   ├── privacy.css       # Privacy page styling
│   ├── privacy.min.css   # Minified privacy CSS
│   ├── not-found.css     # 404 page styling
│   ├── not-found.min.css # Minified 404 CSS
│   ├── rtl.css           # RTL language support
│   ├── rtl.min.css       # Minified RTL CSS
│   ├── fontastic.css     # Fontastic icon font styles
│   ├── fontastic.min.css # Minified fontastic
│   ├── animate.css       # CSS animations
│   ├── animate.min.css   # Minified animations
│   ├── sina-nav.css      # Sina navigation styles
│   └── sina-nav.min.css  # Minified sina-nav
├── js/                   # JavaScript
│   ├── front.js          # Main frontend logic
│   ├── front.min.js      # Minified version
│   ├── search.js         # AJAX search
│   ├── search.min.js     # Minified search
│   ├── unlock-post.js    # Protected post unlock
│   ├── unlock-post.min.js # Minified unlock
│   ├── comment-submission.js  # AJAX comment submission
│   ├── comment-submission.min.js # Minified comment submission
│   ├── load-comment.js   # Dynamic comment loading
│   ├── load-comment.min.js # Minified comment loading
│   ├── cookie-consent.js # Cookie consent handler
│   ├── cookie-consent.min.js # Minified cookie consent
│   ├── rtl.js            # RTL support
│   ├── rtl.min.js        # Minified RTL JS
│   ├── validator.min.js  # Form validation (minified only)
│   ├── jquery.marquee.min.js  # Marquee animation
│   ├── jquery.pause.min.js    # Pause animation
│   ├── jquery.easing.min.js   # Easing effects
│   ├── wow.min.js        # Scroll animations
│   ├── sina-nav.min.js   # Sina navigation JS
│   ├── html5shiv.min.js  # IE HTML5 support
│   └── respond.min.js    # IE responsive support
├── vendor/               # Third-party libraries
│   ├── bootstrap/        # Bootstrap 4 CSS/JS
│   ├── jquery/           # jQuery
│   ├── font-awesome/     # Font Awesome icons
│   ├── @fancyapps/fancybox/  # Fancybox lightbox
│   ├── popper.js/        # Popper.js (Bootstrap dropdowns)
│   └── jquery.cookie/    # jQuery cookie plugin (optional)
├── fonts/                # Custom fonts
└── img/                  # Theme images
```

### File Purpose Summary

| File | Purpose | Dependencies |
|------|---------|--------------|
| `theme.ini` | Metadata (name, designer, directory) | None |
| `functions.php` | Template tags, helpers, i18n | Core models, DB connection |
| `header.php` | HTML head, nav, CSS loading | `functions.php` |
| `footer.php` | JS loading, footer, cookie consent | `header.php` |
| `home.php` | Homepage (hero, posts, gallery) | `header.php`, `footer.php` |
| `single.php` | Post view + password protection | `header.php`, `footer.php` |
| `page.php` | Static page view | `header.php`, `footer.php` |
| `category.php` | Category archive | `header.php`, `footer.php`, `sidebar.php` |
| `tag.php` | Tag archive | `header.php`, `footer.php`, `sidebar.php` |
| `archive.php` | Monthly archive | `header.php`, `footer.php`, `sidebar.php` |
| `archives.php` | Archive index | `header.php`, `footer.php` |
| `blog.php` | Blog listing | `header.php`, `footer.php`, `sidebar.php` |
| `sidebar.php` | Search, categories, tags, archives | None |
| `404.php` | Error page | `header.php`, `footer.php` |
| `privacy.php` | Privacy policy | `header.php`, `footer.php` |
| `cookie-consent.php` | GDPR banner | None |
| `render-comments.php` | Comment rendering function | `functions.php` |
| `download.php` | Download file info | `header.php`, `footer.php` |
| `download_file.php` | File download countdown | `header.php`, `footer.php` |

---

## 3. theme.ini Configuration

### Format

```ini
[info]
theme_name = "My Custom Theme"
theme_designer = "Your Name"
theme_description = "Description of the theme's features and purpose"
theme_directory = "my-custom-theme"
```

### Field Reference

| Field | Required | Description | Max Length |
|-------|----------|-------------|------------|
| `theme_name` | Yes | Display name shown in admin panel | 100 |
| `theme_designer` | Yes | Author/designer name | 90 |
| `theme_description` | Yes | Brief description of the theme | Unlimited (tinytext) |
| `theme_directory` | Yes | Directory name (must match folder name exactly) | 100 |

> **Important:** `theme_directory` must match the actual folder name under `public/themes/`. The `ThemeDao::findThemeByDirectory()` method uses this to verify theme identity.

---

## 4. Template Loading Pattern (CRITICAL)

### The Golden Rule

**NEVER include `call_theme_header()` or `call_theme_footer()` in your template files.**

The core system (`HandleRequest.php`) automatically loads header and footer:

```
HandleRequest.php loads templates in this sequence:
  1. call_theme_header()   → Loads header.php automatically
  2. call_theme_content()  → Loads the page template (home.php, single.php, etc.)
  3. call_theme_footer()   → Loads footer.php automatically
```

### Correct Template Format

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// NO call_theme_header() here — the system handles it
// Template content starts directly:
?>
<div class="container">
    <!-- Page content here -->
</div>
<?php
// NO call_theme_footer() here — the system handles it
```

### Incorrect (DO NOT USE)

```php
<?php
call_theme_header();  // WRONG — causes duplicate headers and "headers already sent" errors
?>
<div class="container">
    <!-- content -->
</div>
<?php
call_theme_footer();  // WRONG — causes duplicate footers
?>
```

### Why This Rule Exists

1. **"headers already sent" errors**: PHP cannot set HTTP headers (status codes, cookies) after output has started. The Dispatcher sets 404 status codes before template rendering. If a template emits output early (via header/footer includes), header modifications fail.
2. **Duplicate content**: Loading header/footer twice produces invalid HTML (two `<html>`, two `<head>`, duplicate CSS/JS).
3. **Consistent rendering**: The core system guarantees header → content → footer execution order.

---

## 5. Template Hierarchy

The system resolves which template to load based on the route:

| Route | Template(s) Resolved | Fallback |
|-------|---------------------|----------|
| `/` (home) | `home.php` → `index.php` | — |
| `/post/{id}/{slug}` | `single.php` → `index.php` | — |
| `/page/{slug}` | `page.php` → `index.php` | — |
| `/category/{slug}` | `category.php` → `archive.php` → `index.php` | — |
| `/tag/{tag}` | `tag.php` → `index.php` | — |
| `/archive/{mm}/{yyyy}` | `archive.php` → `index.php` | — |
| `/archives` | `archives.php` → `index.php` | — |
| `/blog*` | `blog.php` → `index.php` | — |
| `/{keyword}` (search) | `search.php` → `index.php` | — |
| `/privacy` | `privacy.php` → `index.php` | — |
| `/download/{id}` | `download.php` → `index.php` | — |
| 404 | `404.php` → `index.php` | — |

**Important:** The Dispatcher validates content existence *before* template loading. If content is not found, it sets 404 status and loads `404.php` — it never falls through to the requested template.

---

## 6. Complete Template Reference

### 6.1 home.php

The homepage serves as the site's entry point and typically includes:

- **Hero section**: Full-width background image with site title and call-to-action
- **Sticky page content**: A "featured" page displayed prominently
- **Featured/random posts**: Alternating left-right layout
- **Latest posts grid**: 3-column grid of recent posts
- **Gallery section**: Images with Fancybox lightbox

**Key functions used:**

```php
$featured = featured_post();                  // Random headline post
$sticky = sticky_page();                       // Random sticky page
$random = random_posts(1, 3);                  // Random posts for alternating layout
$latest = latest_posts(6, 0);                  // Latest 6 posts
$galleries = display_galleries(1, 8);          // Gallery images
```

**Key i18n keys used:**
- `home.hero.discover_more`
- `home.hero.admin_panel`
- `home.hero.scroll_down`
- `home.intro.welcome`
- `home.latest_posts.title`
- `home.divider.view_more`

### 6.2 single.php (Password-Protected Posts)

The single post template handles both public and password-protected posts:

**Public posts:**
- Featured image via `invoke_frontimg($media_filename)`
- Post title, author, date, comment count
- Content via `htmLawed()` sanitization
- Tags, previous/next navigation
- Comments section (AJAX-loaded)

**Password-protected posts:**
- Shows password form (not content) when `post_visibility === 'protected'`
- AJAX unlock via `/api/v1/posts/{id}/unlock` endpoint
- Rate limiting: max 5 failed attempts per 15 minutes per IP
- Content decryption uses AES-256-CBC with passphrase from database

```php
// In single.php — password form shown when post is protected
if ($post['post_visibility'] === 'protected') {
    // Show unlock form, NOT content
    include 'password-form.php';
} else {
    // Show post content
    echo htmLawed($post['post_content'], $htmLawedConfig);
}
```

**Key JS dependencies:**
- `assets/js/unlock-post.js` — handles AJAX unlock
- `assets/js/comment-submission.js` — handles AJAX comment posting
- `assets/js/load-comment.js` — loads comments dynamically

**Security notes:**
- The Dispatcher includes protected posts in `FrontHelper::grabPreparedFrontPostById()`
- Admin edit flow auto-decrypts content via `decrypt_post_admin()`
- Passwords verified against bcrypt hash in `post_password` column
- Never expose the passphrase to the frontend

### 6.3 page.php

Static pages display with:
- Featured image
- Page title and metadata
- Content with HTML filtering
- Tags display

### 6.4 category.php, tag.php, archive.php, blog.php

Archive templates share a common structure:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
?>
<!-- Archive header -->
<div class="archive-header">
    <h1><?= t('category.title', ['%name%' => htmlout($topicName)]); ?></h1>
</div>

<!-- Post grid (2 columns) -->
<div class="row post-list">
    <?php foreach ($posts as $post) : ?>
    <div class="col-md-6 post-item">
        <!-- Thumbnail, title, excerpt, metadata -->
    </div>
    <?php endforeach; ?>
</div>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Pagination -->
<?php if (function_exists('front_paginator')) front_paginator($totalPages, $currentPage); ?>
```

### 6.5 sidebar.php

Sidebar widgets include:
- **Search form**: AJAX-powered with CSRF protection
- **Latest posts**: 5 most recent posts with thumbnails
- **Categories**: List with post counts
- **Archives**: Monthly archive links with post counts
- **Tags**: Tag cloud

### 6.6 404.php

Simple error page:
- 404 display
- "Page not found" message
- Back to Home link

### 6.7 privacy.php

Privacy policy page — either database-driven or static fallback:
- Policy content
- Last updated date
- Contact information
- Back to Home button

### 6.8 cookie-consent.php

GDPR cookie consent banner:
- Privacy notice text
- Accept / Reject / Learn More buttons
- API integration for consent management
- Cookie categories: necessary (session), analytics (90d), functional (1yr), marketing (30d)

### 6.9 download.php, download_file.php

Download page templates:
- File information display
- Download button with UUID-based URL
- Copy link functionality
- Expiration countdown timer
- Optional support URL

### 6.10 render-comments.php

- `render-comments.php`: Function that renders the comments section HTML with AJAX loading

### 6.11 archives.php

Archive index page listing all archive months grouped by year:

```
2026
├── June (3 posts)
├── May (5 posts)
├── April (2 posts)
└── March (7 posts)
2025
├── December (4 posts)
...
```

---

## 7. Theme Functions (functions.php) Complete Reference

### 7.1 i18n Functions

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `t()` | `(string $key, array $params = []): string` | Translate a string with optional parameter interpolation | Translated string, or key if not found |
| `locale_url()` | `(string $path = '', ?string $locale = null): string` | Generate URL with locale prefix (when enabled) | Full URL string |
| `get_locale()` | `(): string` | Get current frontend locale | e.g., `'en'`, `'ar'`, `'id'` |
| `available_locales()` | `(): array` | Get all available locales | `['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id']` |
| `is_rtl()` | `(): bool` | Check if current locale is RTL | `true` for Arabic |
| `get_html_dir()` | `(): string` | Get HTML `dir` attribute value | `'ltr'` or `'rtl'` |
| `language_switcher()` | `(array $args = []): string` | Generate language switcher HTML | HTML dropdown markup |
| `get_language_name()` | `(string $locale, bool $native = false): string` | Get language display name | e.g., `'English'` or `'العربية'` |
| `detect_browser_locale()` | `(): string` | Detect locale from browser Accept-Language header | Locale code, falls back to `'en'` |

### 7.2 Model Initialization Functions

| Function | Description | Returns |
|----------|-------------|---------|
| `request_path()` | Get the current request path object | `RequestPath` object |
| `initialize_post()` | Initialize `PostModel` singleton | `PostModel` instance |
| `initialize_page()` | Initialize `PageModel` singleton | `PageModel` instance |
| `initialize_comment()` | Initialize `CommentModel` singleton | `CommentModel` instance |
| `initialize_archive()` | Initialize `ArchivesModel` singleton | `ArchivesModel` instance |
| `initialize_topic()` | Initialize `TopicModel` singleton | `TopicModel` instance |
| `initialize_tag()` | Initialize `TagModel` singleton | `TagModel` instance |
| `initialize_gallery()` | Initialize `GalleryModel` singleton | `GalleryModel` instance |

### 7.3 Post Retrieval Functions

| Function | Description | Returns |
|----------|-------------|---------|
| `featured_post()` | Get random post with `post_headlines = 'Y'` | Array or null |
| `get_slideshow(int $limit)` | Get posts with media for slideshow | Array of posts |
| `sticky_page()` | Get random published page (`post_type = 'page'`) | Array or null |
| `random_posts(int $start, int $end)` | Get random published posts in range | Array of posts |
| `latest_posts(int $limit, int $position)` | Get latest posts with offset | Array of posts |
| `retrieve_blog_posts()` | Get all published blog posts | Array of posts |
| `retrieve_detail_post(int $id)` | Get single post by ID (includes protected) | Array or null |
| `posts_by_archive(string $values)` | Get posts by archive month/year string | Array of posts |
| `archive_index()` | Get all archive months with post counts | Array of archives |
| `posts_by_tag(string $tag)` | Get posts matching a tag | Array of posts |
| `searching_by_tag(string $tag)` | Full-text search by tag | Array of posts |
| `posts_by_category(int $topicId)` | Get posts by category/topic ID | Array of posts |
| `retrieve_page(mixed $arg, bool $rewrite)` | Get page by ID or slug | Array or null |
| `retrieve_archives()` | Get archives for sidebar widget | Array of archives |

### 7.4 Navigation Functions

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `front_navigation()` | `(int $parent, array $menu): string` | Render navigation menu recursively | HTML string |
| `theme_navigation()` | `(string $visibility = 'public'): array` | Get menu items filtered by locale and visibility | Array of menu items |
| `convert_menu_link()` | `(string $link, bool $permalinkEnabled): string` | Convert menu link between SEO-friendly and query string format | Converted URL string |
| `link_tag()` | `(int $postId): string` | Generate tag links for a post | HTML string |
| `link_topic()` | `(int $postId): string` | Generate category links for a post | HTML string |
| `previous_post()` | `(int $postId): string` | Get previous post navigation link | HTML string |
| `next_post()` | `(int $postId): string` | Get next post navigation link | HTML string |

### 7.5 Utility Functions

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `total_comment()` | `(int $postId): int` | Count approved comments for a post | Integer count |
| `block_csrf()` | `(): string` | Generate CSRF token for comment form | Hidden input HTML |
| `retrieves_topic_simple()` | `(int $postId): array` | Get topic IDs for a post | Array of topic IDs |
| `retrieves_topic_prepared()` | `(int $postId): array` | Get prepared topic data for a post | Array of topics |
| `sidebar_topics()` | `(): array` | Get active topics with post counts | Array of topics |
| `retrieve_tags()` | `(): array` | Get all tags from posts | Array of unique tags |
| `display_galleries()` | `(int $start, int $limit): array` | Get gallery media items | Array of media |
| `render_comments_section()` | `(int $postId, int $offset): string` | Render comments section HTML | HTML string |
| `nothing_found()` | `(): string` | Display "no posts found" message | HTML string |
| `retrieve_site_url()` | `(): string` | Get site base URL from config | URL string |
| `make_date()` | `(string $timestamp): string` | Format date for display | Formatted date string |
| `htmlout()` | `(string $string): string` | Escape HTML for safe output | Escaped string |
| `get_ip_address()` | `(): string` | Get client IP address | IP string |
| `app_url()` | `(): string` | Get application base URL | URL string |
| `app_sitename()` | `(): string` | Get site name from settings | Site name string |
| `app_tagline()` | `(): string` | Get site tagline from settings | Tagline string |
| `theme_dir()` | `(): string` | Get current theme directory URL | Path string |

---

## 8. Navigation & i18n URL Compatibility

### Overview

The navigation system must work seamlessly with both URL schemes:

| Permalink Status | Menu Link Format | Language Switcher Format |
|-----------------|------------------|--------------------------|
| **Disabled** | Query string (`?p=1`, `?pg=1`, `?cat=1`, `?a=032025`) | `?switch-lang=XX&redirect=...` |
| **Enabled** | SEO-friendly (`/post/1/slug`, `/page/slug`, `/category/slug`) | `locale_url()` with proper prefix |

### convert_menu_link() Logic

Located in `functions.php`, this function converts links dynamically:

```php
function convert_menu_link(string $link, bool $permalinkEnabled): string
{
    // Skip external links, anchors, and special links
    if (empty($link) || $link === '#' || strpos($link, '://') !== false
        || strpos($link, 'mailto:') !== false || strpos($link, '#') === 0) {
        return $link;
    }

    if ($permalinkEnabled) {
        // Convert ?p={id} → /post/{id}/{slug}
        // Convert ?pg={id} → /page/{slug}
        // Convert ?cat={id} → /category/{slug}
        // Convert ?a={yyyymm} → /archive/{mm}/{yyyy}
    } else {
        // Convert /post/{id}/{slug} → ?p={id}
        // Convert /page/{slug} → ?pg={id}
        // Convert /category/{slug} → ?cat={id}
        // Convert /archive/{mm}/{yyyy} → ?a={yyyymm}
    }
    return $link;
}
```

### locale_url() Behavior

```php
function locale_url(string $path = '', ?string $locale = null): string
{
    // Priority: parameter > session > default
    $targetLocale = $locale ?? get_locale();
    $defaultLocale = 'en'; // configurable via settings

    // When permalinks disabled: never add prefix
    if (!is_permalink_enabled()) {
        return $path;
    }

    // When permalinks enabled but prefix toggle off
    if (is_permalink_enabled() && !is_locale_prefix_enabled()) {
        return $path;
    }

    // Default language: no prefix
    if ($targetLocale === $defaultLocale) {
        return $path;
    }

    // Non-default language: add prefix
    return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
}
```

### Language Switcher in header.php

```php
$permalinksEnabled = is_permalink_enabled() === 'yes';

foreach (available_locales() as $locale) :
    if (!$permalinksEnabled) {
        $langUrl = '?switch-lang=' . urlencode($locale)
                 . '&redirect=' . urlencode($_SERVER['REQUEST_URI']);
    } else {
        $langUrl = locale_url($_SERVER['REQUEST_URI'], $locale);
    }
    ?>
    <a href="<?= htmlout($langUrl); ?>"
       class="dropdown-item <?= (get_locale() === $locale) ? 'active' : ''; ?>">
        <?= htmlout(get_language_name($locale, true)); ?>
    </a>
<?php endforeach; ?>
```

### theme_navigation() Locale Filtering

```php
function theme_navigation($visibility = 'public')
{
    $currentLocale = get_locale();

    $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility, parent_id, menu_sort
            FROM tbl_menu
            WHERE menu_status = 'Y'
              AND menu_visibility = ?
              AND (menu_locale = ? OR menu_locale IS NULL OR menu_locale = '')
            ORDER BY menu_sort ASC";

    // ... execute query and return menu items filtered by locale
}
```

---

## 9. Asset Management

### 9.1 CSS Files

All CSS uses `media="print" onload="this.media='all'"` for non-blocking loading with `<noscript>` fallback.

| File | Purpose | Load Condition |
|------|---------|---------------|
| `bootstrap.min.css` | Bootstrap 4 grid, utilities, components | Always |
| `font-awesome.min.css` | Icon set (social, nav, UI) | Always |
| `fontastic.min.css` | Custom icon font | Always |
| `jquery.fancybox.min.css` | Fancybox lightbox styles | Always |
| `style.sea.min.css` | Main theme stylesheet | Always |
| `custom.min.css` | Custom overrides, search dropdown | Always |
| `not-found.min.css` | 404 page styling | Always |
| `privacy.min.css` | Privacy policy page styling | Always |
| `comment.min.css` | Comment section styling | Always |
| `animate.min.css` | CSS animation library (WOW.js) | Always |
| `sina-nav.min.css` | Sina navigation bar styling | Always |
| `cookie-consent.min.css` | Cookie consent banner | Always |
| `rtl.min.css` | RTL layout overrides | Only when `is_rtl()` |

### 9.2 JavaScript Files

All scripts after jQuery use `defer` attribute for non-blocking execution. jQuery loads synchronously (no `defer`).

| File | Purpose | Load Method |
|------|---------|-------------|
| `jquery.min.js` | DOM manipulation, AJAX foundation | Synchronous (required first) |
| `popper.min.js` (vendor) | Bootstrap dropdown positioning | `defer` |
| `bootstrap.min.js` | Bootstrap UI components | `defer` |
| `jquery.cookie.js` | Cookie read/write | `defer` |
| `jquery.fancybox.min.js` | Image gallery lightbox | `defer` |
| `front.min.js` | Main frontend logic | `defer` |
| `jquery.marquee.min.js` | Marquee text animation | `defer` |
| `jquery.pause.min.js` | Animation pause support | `defer` |
| `jquery.easing.min.js` | Custom easing effects | `defer` |
| `comment-submission.min.js` | AJAX comment posting | `defer` |
| `load-comment.min.js` | Dynamic comment loading | `defer` |
| `validator.min.js` | Form validation | `defer` |
| `wow.min.js` | Scroll-triggered animations | `defer` |
| `sina-nav.min.js` | Sina navigation behavior | `defer` |
| `cookie-consent.min.js` | Cookie consent interaction | `defer` |
| `search.min.js` | AJAX search | `defer` |
| `unlock-post.min.js` | Protected post unlock | `defer` |
| `rtl.min.js` | RTL-specific JS | `defer`, conditional on `is_rtl()` |

### 9.3 Vendor Libraries

| Library | Version | Files | Used For |
|---------|---------|-------|----------|
| **Bootstrap** | 4 | `bootstrap.min.css`, `bootstrap.min.js` | Layout, components, responsive grid |
| **jQuery** | 3 | `jquery.min.js` | DOM manipulation, AJAX |
| **Font Awesome** | 4 | `font-awesome.min.css` | Icons (social, navigation, UI) |
| **Fancybox** | 3 | `jquery.fancybox.min.css`, `jquery.fancybox.min.js` | Image gallery lightbox |
| **Popper.js** | 1 | `popper.min.js` | Bootstrap dropdowns, tooltips, popovers |
| **jQuery.cookie** | 1 | `jquery.cookie.js` | Cookie read/write for consent management |

### 9.4 Load Order (footer.php)

JavaScript load order is critical for proper functionality. jQuery must load **synchronously** (no `defer`). All subsequent scripts use `defer` to preserve execution order without blocking page render.

```html
<!-- jQuery MUST load first (synchronous) -->
<script src="assets/vendor/jquery/jquery.min.js"></script>
<!-- Popper.js MUST load before Bootstrap JS -->
<script src="assets/vendor/popper.js/umd/popper.min.js" defer></script>
<!-- Bootstrap JS -->
<script src="assets/vendor/bootstrap/js/bootstrap.min.js" defer></script>
<!-- Cookie plugin (used by other scripts) -->
<script src="assets/vendor/jquery.cookie/jquery.cookie.js" defer></script>
<!-- Fancybox lightbox -->
<script src="assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js" defer></script>
<!-- Theme frontend core -->
<script src="assets/js/front.min.js" defer></script>
<!-- Animation libraries (order: marquee → pause → easing) -->
<script src="assets/js/jquery.marquee.min.js" defer></script>
<script src="assets/js/jquery.pause.min.js" defer></script>
<script src="assets/js/jquery.easing.min.js" defer></script>
<!-- Feature-specific -->
<script src="assets/js/comment-submission.min.js" defer></script>
<script src="assets/js/load-comment.min.js?v=1.2" defer></script>
<script src="assets/js/validator.min.js" defer></script>
<script src="assets/js/wow.min.js" defer></script>
<script src="assets/js/sina-nav.min.js" defer></script>
<script src="assets/js/cookie-consent.min.js" defer></script>
<script src="assets/js/search.min.js" defer></script>
<script src="assets/js/unlock-post.min.js" defer></script>
<!-- RTL support (conditional) -->
<script src="assets/js/rtl.min.js" defer></script>
```

### 9.5 Minification Workflow

Use the CLI minification script to generate production-ready assets:

```bash
php tmp/minify.php
```

**What it does:**
- Scans `public/themes/*/assets/css/` for `.css` files (skips `.min.css`)
- Scans `public/themes/*/assets/js/` for `.js` files (skips `.min.js`)
- Generates corresponding `.min.css` and `.min.js` versions
- Removes comments, whitespace, and redundant characters

**When to use:**
- After modifying source CSS/JS files
- Before deploying to production
- Before committing changes

> **Development mode:** Load source files directly (`style.css`, `front.js`) for debugging
> **Production mode:** Load minified files (`style.sea.min.css`, `front.min.js`) for performance

---

## 10. Image Handling System

### Image Storage Structure

```
public/files/pictures/
├── small/           # Thumbnail images (640x450)
│   └── small_*.jpg  / .webp
├── medium/         # Medium images (730x486)
│   └── medium_*.jpg / .webp
├── large/          # Large images (1200x630)
│   └── large_*.jpg  / .webp
├── *.jpg           # Original JPEG versions
└── *.webp          # WebP versions (shared root)
```

### Image Dimensions

| Size | Width | Height | Directory | Prefix |
|------|-------|--------|-----------|--------|
| thumbnail | 640 | 450 | `small/` | `small_` |
| medium | 730 | 486 | `medium/` | `medium_` |
| large | 1200 | 630 | `large/` | `large_` |

### Image Helper Functions

```php
// Featured image (simple)
invoke_frontimg(string $media_filename, bool $image_thumb = true): string

// Responsive <picture> element with WebP support
invoke_responsive_image(
    string $filename,
    string $size = 'thumbnail',    // 'thumbnail', 'medium', 'large'
    bool $image_thumb = true,
    string $alt = '',
    string $class = 'img-fluid',
    bool $fetchpriority = false,   // true for hero/LCP images
    string $decoding = 'auto'
): string

// Hero image with fetchpriority="high" (LCP optimization)
invoke_hero_image(string $filename, string $alt = '', string $class = 'img-fluid'): string

// Gallery image with lazy loading
invoke_gallery_image(string $filename, string $alt = ''): string
```

### Image Display in Templates

```php
<!-- Hero/LCP image — high priority loading -->
<?= invoke_hero_image($post['media_filename'], $post['post_title']); ?>

<!-- Responsive image with WebP fallback -->
<?= invoke_responsive_image($post['media_filename'], 'medium', true, $post['post_title']); ?>

<!-- Simple featured image (thumbnail) -->
<?= invoke_frontimg($post['media_filename']); ?>

<!-- Gallery images with lightbox -->
<a href="<?= app_url() . '/' . APP_IMAGE . rawurlencode($image['media_filename']); ?>"
   data-fancybox="gallery" data-caption="<?= htmlout($image['media_caption']); ?>">
    <?= invoke_gallery_image($image['media_filename'], $image['media_caption']); ?>
</a>
```

### Path Constants

Defined in `lib/common.php`:

```php
define('APP_IMAGE', APP_PUBLIC . DS . 'files' . DS . 'pictures' . DS);
define('APP_IMAGE_LARGE', APP_IMAGE . 'large' . DS);
define('APP_IMAGE_MEDIUM', APP_IMAGE . 'medium' . DS);
define('APP_IMAGE_SMALL', APP_IMAGE . 'small' . DS);
```

**Always use these constants** for image paths. Never hardcode paths like `'/public/files/pictures/'`.

---

## 11. i18n Integration for Themes

### Architecture Overview

The frontend uses a **separate** locale system from the admin panel:

| Aspect | Frontend | Admin Panel |
|--------|----------|-------------|
| Session var | `$_SESSION['scriptlog_locale']` | `$_SESSION['admin_locale']` |
| Cookie | `scriptlog_locale` | `admin_locale` |
| URL param | `?switch-lang=` | `?lang=` |
| Functions | `get_locale()`, `set_locale()` | `admin_get_locale()`, `admin_set_locale()` |

This ensures frontend language changes never affect admin panel, and vice versa.

### Translation Flow

```
User selects language
    → ?switch-lang=id
    → lib/main.php saves to $_SESSION['scriptlog_locale']
    → Cookie set: scriptlog_locale=id
    → Redirect to clean URL (no query params)
    → Page loads with new locale via get_locale()
    → Theme renders with t() translations
```

### Translation Sources (Priority Order)

1. **JSON files**: `public/themes/blog/lang/{locale}.json` (fast, file-based)
2. **In-memory cache**: Loaded JSON data cached during page lifecycle
3. **Key as-is**: If no translation found, return the key string

### Translation Key Naming Convention

```
namespace.key        →  "sidebar.search.title"
namespace.sub.key    →  "cookie_consent.buttons.accept"
```

**Good:** `header.nav.home`, `sidebar.latest_posts.title`, `form.name.label`
**Bad:** `navHome`, `sidebarLatestPosts`, `formNameLabel`

### Using Translations in Templates

```php
<!-- Basic translation -->
<h2><?= t('sidebar.latest_posts.title'); ?></h2>

<!-- With parameter interpolation -->
<a href="<?= locale_url('/post/1/slug'); ?>"><?= t('home.hero.discover_more'); ?></a>

<!-- With placeholder replacement -->
<p><?= t('home.intro.welcome', ['%name%' => $siteName]); ?></p>

<!-- HTML direction for RTL -->
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">

<!-- Conditional RTL CSS loading -->
<?php if (is_rtl()): ?>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.min.css">
<?php endif; ?>
```

### Translation Key Dictionary

#### Navigation (header.nav.*)
| Key | English |
|-----|---------|
| `header.nav.home` | Home |
| `header.nav.blog` | Blog |
| `header.nav.about` | About |

#### Sidebar (sidebar.*)
| Key | English |
|-----|---------|
| `sidebar.search.title` | Search |
| `sidebar.search.placeholder` | What are you looking for? |
| `sidebar.latest_posts.title` | Latest Posts |
| `sidebar.categories.title` | Categories |
| `sidebar.archives.title` | Archives |
| `sidebar.tags.title` | Tags |

#### Home (home.*)
| Key | English |
|-----|---------|
| `home.hero.discover_more` | Discover More |
| `home.hero.scroll_down` | Scroll Down |
| `home.intro.welcome` | Welcome to %name% |
| `home.latest_posts.title` | Latest from the blog |
| `home.divider.view_more` | View More |

#### Single Post (single.*, form.*)
| Key | English |
|-----|---------|
| `single.comment.leave_reply` | Leave a comment |
| `single.comment.label` | Type your comment |
| `single.comment.submit` | Submit Comment |
| `form.name.label` | Name |
| `form.email.label` | Email (will not be published) |

#### Footer
| Key | English |
|-----|---------|
| `footer.copyright` | All rights reserved |

#### Cookie Consent (cookie_consent.*)
| Key | English |
|-----|---------|
| `cookie_consent.banner.title` | We value your privacy |
| `cookie_consent.buttons.accept` | Accept All |
| `cookie_consent.buttons.reject` | Reject All |
| `cookie_consent.buttons.learn_more` | Learn More |

#### 404
| Key | English |
|-----|---------|
| `404.title` | 404 |
| `404.message` | The page you were looking for was not found. |
| `404.back_home` | Back to Home |

### Supported Languages

| Code | Language | Direction |
|------|----------|-----------|
| en | English | LTR |
| ar | العربية | RTL |
| zh | 中文 | LTR |
| fr | Français | LTR |
| ru | Русский | LTR |
| es | Español | LTR |
| id | Bahasa Indonesia | LTR |

### JSON Translation File Format (`lang/en.json`)

```json
{
  "header.nav.home": "Home",
  "header.nav.blog": "Blog",
  "sidebar.search.title": "Search",
  "sidebar.latest_posts.title": "Latest Posts",
  "home.hero.discover_more": "Discover More",
  "footer.copyright": "All rights reserved"
}
```

---

## 12. Security Considerations

### 12.1 File Access Guard

Every PHP file must start with:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
```

This prevents direct URL access to template files (e.g., `https://example.com/public/themes/blog/home.php`).

### 12.2 Output Escaping

Always escape output to prevent XSS:

```php
// CORRECT — escape all dynamic output
<?= htmlout($post['post_title']); ?>
<a href="<?= htmlout($postUrl); ?>"><?= htmlout($post['post_title']); ?></a>

// CORRECT — content with HTML allowed (sanitized, not escaped)
<?= htmLawed($post['post_content'], $htmLawedConfig); ?>
```

### 12.3 CSRF Protection

All forms must include CSRF tokens:

```php
<form method="post" action="">
    <?= block_csrf(); ?>  <!-- Generates hidden CSRF token input -->
    <!-- form fields -->
</form>
```

### 12.4 Password-Protected Posts

Security architecture for protected content:

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Password storage | bcrypt (`post_password`) | One-way password verification |
| Encryption key | MD5(app_key + password) → `passphrase` | Derives AES key deterministically |
| Content encryption | AES-256-CBC | Encrypts post content in database (value stored in `post_content`) |
| Rate limiting | File-based, 5 attempts/15 min | Prevents brute force |
| Admin decryption | `decrypt_post_admin()` | Admin bypass without password |

### 12.5 Cookie Consent & GDPR

The cookie consent banner must:
- Be displayed on first visit (no `cookie_consent` cookie)
- Offer Accept, Reject, and Learn More options
- Link to privacy policy page
- Track consent via API endpoint
- Not set analytics/marketing cookies without consent

### 12.6 Security Checklist for Theme Development

- [ ] All PHP files have `defined('SCRIPTLOG') || die()` guard
- [ ] All dynamic output uses `htmlout()` or `htmlspecialchars()`
- [ ] All forms include CSRF token via `block_csrf()`
- [ ] User-submitted content is sanitized with `htmLawed()`
- [ ] No database queries in templates (use functions.php helpers)
- [ ] No direct `include`/`require` of files from `$_GET` parameters
- [ ] Password-protected posts never expose passphrase to frontend
- [ ] Cookie consent banner is GDPR-compliant
- [ ] No `http_response_code()` in templates (Dispatcher handles it)
- [ ] Theme does not expose absolute server paths

---

## 13. Creating a Custom Theme — Step-by-Step

### Step 1: Create Theme Directory

```bash
mkdir -p public/themes/my-theme/{css,js,img,fonts,vendor,lang}
mkdir -p public/themes/my-theme/assets/{css,js,vendor,fonts,img}
```

### Step 2: Create theme.ini

```ini
[info]
theme_name = "My Custom Theme"
theme_designer = "Your Name"
theme_description = "A beautiful custom theme for Blogware"
theme_directory = "my-theme"
```

### Step 3: Create functions.php

Copy from `public/themes/blog/functions.php` and customize:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// i18n functions
function get_locale(): string {
    return $_SESSION['scriptlog_locale'] ?? 'en';
}

function is_rtl(): bool {
    return get_locale() === 'ar';
}

// Translation function — loads from JSON files
function t(string $key, array $params = []): string {
    static $translations = [];
    $locale = get_locale();
    // Load JSON file, cache, and return translation
    // ...
}

// Post retrieval helpers
function latest_posts(int $limit = 5, int $position = 0): array {
    $postModel = initialize_post();
    return $postModel->getLatestPosts($limit, $position);
}

// Navigation
function theme_navigation($visibility = 'public'): array {
    // Query tbl_menu filtered by locale
}

function front_navigation(int $parent, array $menu): string {
    // Recursive menu rendering
}

// Utility
function htmlout(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

### Step 4: Create header.php

```html
<!DOCTYPE html>
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlout($pageTitle ?? 'My Blog'); ?></title>
    <link rel="alternate" type="application/rss+xml" title="RSS" href="<?= app_url(); ?>/rss.php">

    <!-- Non-blocking CSS loading pattern -->
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/comment.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/comment.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/cookie-consent.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/cookie-consent.min.css"></noscript>

    <?php if (is_rtl()): ?>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.min.css">
    <?php endif; ?>
    <link rel="shortcut icon" href="<?= theme_dir(); ?>assets/img/favicon.ico">
</head>
<body>
<!-- Skip link for keyboard users -->
<a class="skip-link" href="#main-content"><?= t('skip_to_content'); ?></a>
<header role="banner">
<nav class="navbar navbar-expand-lg" role="navigation" aria-label="<?= t('nav.main_navigation'); ?>">
    <div class="container">
        <a class="navbar-brand" href="<?= app_url(); ?>"><?= htmlout(app_sitename()); ?></a>
        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarNav"
                aria-label="Menu" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <?php
                $navItems = theme_navigation('public');
                echo front_navigation(0, $navItems);
                ?>
            </ul>
            <!-- Language Switcher (permalink-aware) -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false"
                            aria-label="<?= t('language_switcher.label'); ?>">
                        <?= strtoupper(get_locale()); ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php
                        $permalinks = is_permalink_enabled() === 'yes';
                        foreach (available_locales() as $locale): ?>
                        <a class="dropdown-item <?= (get_locale() === $locale) ? 'active' : ''; ?>"
                           href="<?= $permalinks ? locale_url($_SERVER['REQUEST_URI'], $locale) : '?switch-lang=' . urlencode($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI']); ?>"
                           aria-label="<?= htmlout(get_language_name($locale, false)); ?>">
                            <?= htmlout(get_language_name($locale, true)); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
</header>
<main id="main-content" role="main">
```

### Step 5: Create footer.php

```html
</main>

<footer class="bg-primary text-white mt-5 py-4" role="contentinfo">
    <div class="container text-center">
        <p>&copy; <?= date('Y'); ?> <?= t('footer.copyright'); ?>. <?= htmlout(app_sitename()); ?></p>
    </div>
</footer>

<!-- Scripts — jQuery MUST load first (synchronous), rest uses defer -->
<script src="<?= theme_dir(); ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/popper.js/umd/popper.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/bootstrap.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/front.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/comment-submission.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/load-comment.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/cookie-consent.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/search.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/unlock-post.min.js" defer></script>

<?php if (is_rtl()): ?>
<script src="<?= theme_dir(); ?>assets/js/rtl.min.js" defer></script>
<?php endif; ?>

<!-- Cookie Consent Banner -->
<?php
if (function_exists('should_show_consent_banner') && should_show_consent_banner()) {
    if (file_exists(__DIR__ . '/cookie-consent.php')) {
        include __DIR__ . '/cookie-consent.php';
    }
}
?>
</body>
</html>
```

### Step 6: Create Template Files

Each template follows the same pattern — no header/footer includes, just the content section.

See [Section 6](#6-complete-template-reference) for detailed template descriptions.

### Step 7: Add Translation Files

Create `lang/en.json` as a minimal starting point:

```json
{
  "header.nav.home": "Home",
  "header.nav.blog": "Blog",
  "sidebar.search.title": "Search",
  "sidebar.latest_posts.title": "Latest Posts",
  "home.hero.scroll_down": "Scroll Down",
  "home.latest_posts.title": "Latest from the blog",
  "footer.copyright": "All rights reserved",
  "404.title": "404",
  "404.message": "Page not found",
  "404.back_home": "Back to Home",
  "cookie_consent.banner.title": "We value your privacy",
  "cookie_consent.buttons.accept": "Accept All"
}
```

### Step 8: Add Vendor Libraries

Copy required vendor libraries from the blog theme:

```bash
cp -r public/themes/blog/assets/vendor/bootstrap public/themes/my-theme/assets/vendor/
cp -r public/themes/blog/assets/vendor/jquery public/themes/my-theme/assets/vendor/
cp -r public/themes/blog/assets/vendor/font-awesome public/themes/my-theme/assets/vendor/
cp -r public/themes/blog/assets/vendor/popper.js public/themes/my-theme/assets/vendor/
cp -r public/themes/blog/assets/vendor/@fancyapps public/themes/my-theme/assets/vendor/
```

### Step 9: Minify Assets

```bash
php tmp/minify.php
```

### Step 10: Register in Admin Panel

1. Log in as administrator
2. Navigate to **Appearance → Templates** (`admin/index.php?load=templates`)
3. Click **Activate** next to your theme

Alternatively, activate directly via database or CLI:

```sql
UPDATE tbl_themes SET theme_status = 'N';
INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status)
VALUES ('My Custom Theme', 'A beautiful custom theme', 'Your Name', 'my-theme', 'Y');
```

---

## 14. Theme Registration & Activation

### How Theme Activation Works

1. Admin clicks "Activate" → `ThemeController::activateTheme($id)`
2. `ThemeService::activateInstalledTheme()`:
   - Sets all themes to inactive (`theme_status = 'N'`)
   - Sets selected theme to active (`theme_status = 'Y'`)
3. Frontend immediately uses the new theme

### Database Schema (`tbl_themes`)

| Column | Type | Description |
|--------|------|-------------|
| ID | INT(11) PK | Auto-increment |
| theme_title | VARCHAR(100) | Display name |
| theme_desc | tinytext | Description |
| theme_designer | VARCHAR(90) | Author name |
| theme_directory | VARCHAR(100) | Directory name |
| theme_status | ENUM('Y','N') | Active/inactive |

### Fallback Logic

If no theme is active in the database, or if the active theme's directory is missing, the system falls back to the `blog` theme:

```php
// In ThemeDao::loadTheme()
$activeTheme = $this->findRow(['Y']);  // Find active theme
if (empty($activeTheme)) {
    // Fallback to blog theme
    return $this->findRow(['blog']);
}
```

This ensures the site never breaks due to a missing or misconfigured theme.

---

## 15. Testing & Quality Assurance

### 15.1 Syntax Checks

```bash
# Check all PHP files for syntax errors
find public/themes/my-theme -name "*.php" -exec php -l {} \;
```

### 15.2 Asset Minification

```bash
php tmp/minify.php
```

Verify both source and minified files exist:
```bash
ls public/themes/my-theme/assets/css/*.min.css
ls public/themes/my-theme/assets/js/*.min.js
```

### 15.3 Functional Verification Checklist

After implementing your theme, verify each feature:

- [ ] **Homepage**: Hero section displays, posts load, gallery works with Fancybox
- [ ] **Single post**: Post content displays, comments load via AJAX, comment form submits
- [ ] **Protected post**: Unlock form shows, correct password reveals content, wrong password shows error, rate limiting kicks in after 5 attempts
- [ ] **Pages**: Static page renders correctly
- [ ] **Categories**: Category archive lists posts, pagination works
- [ ] **Tags**: Tag archive shows matching posts
- [ ] **Archives**: Monthly archive and archive index both work
- [ ] **Blog**: Blog listing page displays posts
- [ ] **Search**: AJAX search returns results, dropdown appears
- [ ] **Navigation**: Menu links work in both permalink modes
- [ ] **Language switcher**: All 7 languages switch correctly, RTL works for Arabic
- [ ] **404 page**: Custom 404 renders for invalid URLs
- [ ] **Privacy page**: Loads and displays correctly
- [ ] **Cookie consent**: Banner appears on first visit, buttons work
- [ ] **Downloads**: Download page renders, UUID links work
- [ ] **Responsive**: Layout works at 320px, 768px, 1024px, 1440px
- [ ] **Accessibility**: Semantic HTML5, ARIA labels, keyboard navigation
- [ ] **No PHP errors**: Debug mode shows no warnings or notices
- [ ] **Console errors**: Browser console shows no JS errors

### 15.4 Performance Checks

```bash
# Check file sizes (minified assets should be small)
ls -lh public/themes/my-theme/assets/css/*.min.css
ls -lh public/themes/my-theme/assets/js/*.min.js

# Verify no duplicate CSS/JS loading in header/footer
grep -c "stylesheet" public/themes/my-theme/header.php
grep -c "script" public/themes/my-theme/footer.php
```

### 15.5 Comparing with Blog Theme

Use the default blog theme as a reference for correctness:

```bash
# Compare file structure
diff <(cd public/themes/blog && find . -name "*.php" | sort) \
     <(cd public/themes/my-theme && find . -name "*.php" | sort)

# Compare functions.php signatures
grep "^function " public/themes/blog/functions.php | sort > /tmp/blog_fns.txt
grep "^function " public/themes/my-theme/functions.php | sort > /tmp/theme_fns.txt
diff /tmp/blog_fns.txt /tmp/theme_fns.txt
```

---

## 16. Troubleshooting Common Issues

### 16.1 Gallery/Lightbox Not Working

**Symptom:** Clicking gallery image navigates to image URL instead of opening lightbox

**Root cause:** Fancybox CSS/JS not loaded or vendor files missing

**Fix:**
```bash
# Copy vendor files from blog theme
cp -r public/themes/blog/assets/vendor/@fancyapps public/themes/my-theme/assets/vendor/

# Verify header.php loads fancybox CSS
# Verify footer.php loads fancybox JS
```

**Checklist:**
- [ ] `assets/vendor/@fancyapps/fancybox/` directory exists with both `.min.css` and `.min.js`
- [ ] `header.php` has `<link>` for `jquery.fancybox.min.css`
- [ ] `footer.php` has `<script>` for `jquery.fancybox.min.js` (after jQuery, before theme JS)

### 16.2 Language Switcher Dropdown Not Working

**Symptom:** Clicking language switcher does nothing

**Root cause:** Popper.js missing or wrong path

**Fix:**
```bash
# Ensure Popper.js exists at the correct path
ls public/themes/my-theme/assets/vendor/popper.js/umd/popper.min.js

# Verify load order in footer.php:
# 1. jQuery
# 2. Popper.js
# 3. Bootstrap JS
```

**Popper.js MUST load before Bootstrap JS** for dropdowns to work.

### 16.3 Missing JS/CSS Files

**Symptom:** Browser console shows 404 errors for `.js` or `.css` files

**Fix:** Copy missing files from blog theme:

```bash
# CSS
cp public/themes/blog/assets/css/{comment,custom,privacy,not-found}.min.css \
   public/themes/my-theme/assets/css/

# JS
cp public/themes/blog/assets/js/{search,unlock-post,comment-submission,load-comment}.min.js \
   public/themes/my-theme/assets/js/
```

### 16.4 Popper.js Path Inconsistency

**Symptom:** Bootstrap dropdowns not working, or Popper.js loaded from two different paths

**Fix:** Use only one Popper.js location:

```php
<!-- CORRECT — single source -->
<script src="assets/vendor/popper.js/umd/popper.min.js"></script>

<!-- WRONG — don't load from two places -->
<script src="assets/vendor/bootstrap/js/popper.min.js"></script>
<script src="assets/vendor/popper.js/umd/popper.min.js"></script>
```

### 16.5 Duplicate Header/Footer

**Symptom:** Page shows two navigation bars, two footers, or duplicate CSS/JS

**Root cause:** Template file manually calls `call_theme_header()` or `call_theme_footer()`

**Fix:** Remove these calls from all template files. The core system loads header/footer automatically.

```php
// SEARCH for these patterns in ALL template files:
// call_theme_header()
// call_theme_footer()
// include 'header.php'
// include 'footer.php'
// require 'header.php'
// require 'footer.php'
```

### 16.6 theme_meta() PHP Errors in CLI

**Symptom:** "Call to undefined function theme_meta()" when running CLI scripts

**Root cause:** `theme_meta()` relies on `HandleRequest::isQueryStringRequested()` which needs a web context

**Fix:** Replace `theme_meta()` calls with static meta tags:

```php
<!-- INSTEAD OF: -->
<?= theme_meta(); ?>

<!-- USE: -->
<meta name="description" content="My Blog - A great place to read">
<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="<?= app_url(); ?>/rss.php">
```

### 16.7 Password-Protected Post Not Decrypting

**Symptom:** Post shows password form but correct password doesn't unlock content

**Root cause:** Common issues:
1. `unlock-post.js` not loaded in footer
2. API endpoint returning JSON parse error
3. Passphrase mismatch (old encryption bug)

**Debug:**
```bash
# Test unlock API directly
curl -X POST "https://example.com/api/v1/posts/3/unlock" \
  -H "Content-Type: application/json" \
  -d '{"password": "yourPassword"}'

# Expected response:
# {"success":true,"status":200,"data":{"valid":true,"content":"...","title":"..."}}
```

---

## 17. Files Reference

### Core Theme Files

| File | Location | Purpose |
|------|----------|---------|
| `theme.ini` | `public/themes/[theme]/` | Theme metadata configuration |
| `functions.php` | `public/themes/[theme]/` | Template tags, helpers, i18n |
| `header.php` | `public/themes/[theme]/` | HTML head, nav, CSS |
| `footer.php` | `public/themes/[theme]/` | Scripts, footer, cookie consent |
| `home.php` | `public/themes/[theme]/` | Homepage template |
| `single.php` | `public/themes/[theme]/` | Single post view |
| `page.php` | `public/themes/[theme]/` | Static page view |
| `category.php` | `public/themes/[theme]/` | Category archive |
| `tag.php` | `public/themes/[theme]/` | Tag archive |
| `archive.php` | `public/themes/[theme]/` | Monthly archive |
| `archives.php` | `public/themes/[theme]/` | Archive index |
| `blog.php` | `public/themes/[theme]/` | Blog listing |
| `sidebar.php` | `public/themes/[theme]/` | Sidebar widgets |
| `404.php` | `public/themes/[theme]/` | 404 error page |
| `privacy.php` | `public/themes/[theme]/` | Privacy policy |
| `cookie-consent.php` | `public/themes/[theme]/` | Cookie consent banner |
| `download.php` | `public/themes/[theme]/` | Download page |
| `download_file.php` | `public/themes/[theme]/` | Download handler |
| `render-comments.php` | `public/themes/[theme]/` | Comment rendering function |
| `index.php` | `public/themes/[theme]/` | Entry point (usually empty) |

### Core System Integration Files

| File | Location | Purpose |
|------|----------|---------|
| `theme-caller.php` | `lib/utility/theme-caller.php` | `theme_identifier()` — resolves active theme |
| `theme-navigation.php` | `lib/utility/theme-navigation.php` | Navigation data fetching with locale filtering |
| `Dispatcher.php` | `lib/core/Dispatcher.php` | Content validation, template routing |
| `HandleRequest.php` | `lib/core/HandleRequest.php` | Template loading (header → content → footer) |
| `ThemeDao.php` | `lib/dao/ThemeDao.php` | Theme CRUD with fallback |
| `ThemeService.php` | `lib/service/ThemeService.php` | Theme activation business logic |
| `ThemeController.php` | `lib/controller/ThemeController.php` | Theme admin page handling |
| `FrontHelper.php` | `lib/core/FrontHelper.php` | Frontend post/page retrieval helpers |

### Asset Files

| File | Purpose |
|------|---------|
| `assets/css/style.css` | Main theme styles (source) |
| `assets/css/style.sea.min.css` | Minified production CSS |
| `assets/css/custom.css` | Custom overrides |
| `assets/css/comment.css` | Comment section styles |
| `assets/css/privacy.css` | Privacy page styles |
| `assets/css/not-found.css` | 404 page styles |
| `assets/css/cookie-consent.css` | Cookie banner styles |
| `assets/css/rtl.css` | RTL language support |
| `assets/js/front.js` | Main frontend JavaScript |
| `assets/js/search.js` | AJAX search |
| `assets/js/unlock-post.js` | Password unlock |
| `assets/js/comment-submission.js` | AJAX comments |
| `assets/js/load-comment.js` | Comment loading |
| `assets/js/cookie-consent.js` | Cookie consent |
| `assets/js/rtl.js` | RTL support |
| `assets/vendor/bootstrap/` | Bootstrap 4 |
| `assets/vendor/jquery/` | jQuery |
| `assets/vendor/font-awesome/` | Font Awesome icons |
| `assets/vendor/@fancyapps/fancybox/` | Fancybox lightbox |
| `assets/vendor/popper.js/` | Popper.js |

---

## Appendix: Quick Reference

### Template Tags Cheat Sheet

```php
// i18n
t('key')                              // Translate string
locale_url('/path', 'es')             // Localized URL
get_locale()                          // Current locale
is_rtl()                              // RTL check

// Post retrieval
featured_post()                       // Random featured post
latest_posts(5)                       // Latest 5 posts
random_posts(1, 3)                    // 3 random posts
retrieve_detail_post($id)             // Single post by ID
posts_by_category($topicId)           // Posts in category
posts_by_tag($tag)                    // Posts by tag
posts_by_archive('03/2025')           // Posts in archive month

// Navigation
theme_navigation('public')            // Get menu items
front_navigation(0, $items)           // Render menu HTML
convert_menu_link($link, $enabled)    // Convert URL format
link_tag($postId)                     // Tag links for post
link_topic($postId)                   // Category links for post

// Comments
total_comment($postId)                // Comment count
block_csrf()                          // CSRF token
render_comments_section($postId, 0)   // Comment section HTML

// Images
invoke_frontimg($filename)            // Featured image
invoke_responsive_image($file, 'medium')  // Responsive <picture>
invoke_hero_image($file)              // Hero image (high priority)
invoke_gallery_image($file)           // Gallery thumbnail

// Security
htmlout($string)                      // Escape HTML
htmLawed($content, $config)           // Sanitize HTML

// Utility
make_date($timestamp)                 // Format date
retrieve_site_url()                   // Site base URL
app_url()                             // App URL from config
nothing_found()                       // "No posts" message
```

### Recommended Development Workflow

```
1. Plan theme structure (copy blog theme as reference)
2. Create theme directory and theme.ini
3. Implement functions.php with needed helpers
4. Create header.php (HTML head, nav, CSS)
5. Create footer.php (scripts, footer content)
6. Create templates one by one (home → single → page → archives → etc.)
7. Add lang/en.json with translation keys
8. Copy vendor libraries from blog theme
9. Create and minify CSS/JS assets
10. Register theme in admin panel
11. Test all features against checklist (Section 15.3)
12. Fix any issues (see Section 16)
13. Minify assets for production
14. Deploy
```

> **Best Practice:** Always base your theme on `public/themes/blog/` — it contains all required functions, correct template patterns, and working vendor configurations.

---

*End of Theme Developer Guide*
