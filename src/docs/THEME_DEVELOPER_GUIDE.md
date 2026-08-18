# Theme Developer Guide

**Project:** Blogware/Scriptlog CMS  
**Version:** 1.1.0 | **Last Updated:** August 2026

> **Audience:** Theme developers building custom themes from scratch. This guide is a superset of the theming section in [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) and covers the complete theme development lifecycle - from directory structure through testing and troubleshooting.

> **Recent changes (August 2026):** This guide was refreshed to match the theme remediation work (Phases 0-8) and the frontend service migration. The biggest practical differences for theme developers:
>
> - Template files are rendered by the core `ThemeRenderer`, not by templates including each other. Templates never call `call_theme_header()`/`call_theme_footer()`.
> - Output escaping has **one** boundary: `theme_escape_html()`. Avoid the old `htmlout()`-based examples in older blog posts.
> - `functions.php` is now a thin loader. The real helpers live in `functions-i18n.php`, `functions-nav.php`, `functions-post.php`, `functions-media.php`, and `functions-comments.php`.
> - Repeated markup (post cards, pagination, comments, meta rows) lives in `partials/` and is shared by every listing template.
> - The frontend data layer is `Scriptlog\Service\FrontService` (reachable via `front_service()`). The old `FrontHelper` is deprecated and only kept for backward compatibility.
> - Password-protected post resolution is owned by `Scriptlog\Service\ProtectedPostService`, not by the template.

> **HTMX Alternative:** Blogware also ships with the **Valdur** theme (`public/themes/valdur/`), an HTMX-powered theme with zero jQuery dependency. If you're building a new theme and want to use HTMX, vanilla JS, and custom CSS instead of Bootstrap + jQuery, see the [HTMX Theme Developer Guide](HTMX_THEME_DEVELOPER_GUIDE.md). The Valdur theme uses the `is_htmx_request()` backend pattern, `partials/` fragment templates, CSRF via `window.scriptlog_vars`, and a pure CSS design system.

---

## Table of Contents

1. [Overview & Architecture Principles](#1-overview--architecture-principles)
2. [Theme Directory Structure](#2-theme-directory-structure)
3. [theme.ini Configuration](#3-themeini-configuration)
4. [Template Loading Pattern (CRITICAL)](#4-template-loading-pattern-critical)
5. [Template Hierarchy](#5-template-hierarchy)
6. [Complete Template Reference](#6-complete-template-reference)
7. [Theme Functions (functions.php & functions-*.php) Complete Reference](#7-theme-functions-functionsphp--functions-phps-complete-reference)
8. [Navigation & i18n URL Compatibility](#8-navigation--i18n-url-compatibility)
9. [Asset Management](#9-asset-management)
10. [Image Handling System](#10-image-handling-system)
11. [i18n Integration for Themes](#11-i18n-integration-for-themes)
12. [Security Considerations](#12-security-considerations)
13. [Creating a Custom Theme - Step-by-Step](#13-creating-a-custom-theme---step-by-step)
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
| **Scalability** | Templates include only display logic - no database queries in templates. The theme helper files (`functions-*.php`) provide functions that call the model/DAO/service layers, keeping templates thin and maintainable. Multiple themes can coexist. |
| **Security** | All dynamic output is escaped exactly once via `theme_escape_html()` (the single escaping boundary). Content is sanitized via `htmLawed()` on the way in. Forms include CSRF tokens via `block_csrf()`. All PHP files use `defined('SCRIPTLOG') \|\| die()` guard. Password-protected posts are resolved by `ProtectedPostService` using AES-256-CBC encryption with bcrypt password hashes. |
| **Safety** | Template loading is handled by the core system (`ThemeRenderer`) - never manual `include`/`require` of the full page in templates. 404 handling happens in the Dispatcher before any output, preventing "headers already sent" errors. If the active theme is missing templates, the system falls back to the default `blog` theme. |
| **Speed** | Asset minification via `tmp/minify.php`. Lazy loading images with `loading="lazy"`. Minified CSS/JS in production (`*.min.css`, `*.min.js`). Translation caching avoids repeated file reads. |
| **Reliability** | `ThemeDao` includes fallback logic - if the active theme is missing, it defaults to `blog`. `theme_identifier()` in `lib/utility/theme-caller.php` safely resolves theme paths. All PHP files are validated with `php -l` before deployment. |
| **UI/UX (Premium)** | Bootstrap 4 responsive grid. Mobile-first with breakpoints at 768px/1024px/1440px. ARIA labels on all interactive elements. Keyboard navigation (skip link + visible focus). RTL support for Arabic. Fancybox lightbox for galleries. AJAX comment submission and search (with `aria-live` results). Cookie consent banner (GDPR). |

### How the Theme System Works

```
Request → Dispatcher
  1. Compiles the route table (Bootstrap::defineRoutingRules())
  2. Validates the requested content exists (404 check first)
  3. Routes the request to a controller (search, locale, download_file) or
     calls ThemeRenderer::render(routeKey)
  4. ThemeRenderer::render() loads:
     header.php  → HTML head, nav, CSS
     {routeKey}.php → home.php / single.php / page.php / etc.
     footer.php  → scripts, close tags, cookie banner
```

Two important consequences for theme developers:

1. **You never load the header/footer yourself.** Templates only contain the page body - the core renders `header.php` and `footer.php` around them. If you call `call_theme_header()` or `call_theme_footer()` inside a template, you'll get duplicated navigation and scripts.
2. **The route key and the template name match.** The Dispatcher looks up the route (`home`, `single`, `category`, ...) and renders the file with that name plus `.php`. There is no `index.php` fallback chain anymore.

The active theme directory is resolved by `lib/utility/theme-caller.php` → `theme_identifier()`, which reads `tbl_themes`. If no theme is active, `ThemeRenderer` falls back to the bundled `blog` theme.

### Communication Flow: Theme ↔ Core

```
Theme Template (e.g., category.php)
    │
    ├── Calls theme helper functions (loaded by functions.php)
    │   ├── posts_by_category()   → FrontService / TopicModel → DAO → DB
    │   ├── prepare_post_card()   → builds an escaped PostViewModel
    │   ├── prepare_sidebar()     → builds an escaped SidebarViewModel
    │   └── front_navigation()    → MenuDao → DB → MenuViewModel tree
    │
    ├── Includes shared partials
    │   ├── partials/card.php     → post card markup
    │   ├── partials/paginator.php → pagination markup
    │   └── sidebar.php           → sidebar widgets
    │
    ├── Uses security/utility functions
    │   ├── theme_escape_html()   → the single output escaping boundary
    │   ├── block_csrf()          → CSRF token
    │   ├── safe_html()           → for content already stripped of tags
    │   └── invoke_frontimg()     → display images
    │
    └── Uses i18n functions
        ├── t()                  → translate strings (with %param% interpolation)
        ├── locale_url()         → locale-prefixed URLs
        ├── get_locale()         → current locale
        └── is_rtl()             → RTL detection
```

---

## 2. Theme Directory Structure

### Required Files

A complete theme must include these files (the `blog` theme is the reference implementation):

```
public/themes/[theme-name]/
├── theme.ini              # Theme metadata (REQUIRED)
├── functions.php          # Thin loader - requires the helper modules below (REQUIRED)
├── functions-i18n.php     # t(), locale_url(), language_switcher(), etc.
├── functions-nav.php      # front_navigation(), build_menu_tree(), convert_menu_link(), etc.
├── functions-post.php     # initialize_*(), prepare_post_card(), theme_post_url(), etc.
├── functions-media.php    # get_slideshow(), display_galleries(), get_post_thumbnail()
├── functions-comments.php # total_comment(), block_csrf(), render_comments_section()
├── header.php             # HTML head, navigation, CSS assets
├── footer.php             # Scripts, footer content, cookie consent
├── home.php               # Homepage template
├── single.php             # Single post view
├── page.php               # Static page view
├── category.php           # Category archive
├── tag.php                # Tag archive
├── archive.php            # Monthly archive
├── archives.php           # Archive index (all months)
├── blog.php               # Blog listing page
├── search.php             # Search results page
├── sidebar.php            # Sidebar widgets
├── 404.php                # 404 error page
├── privacy.php            # Privacy policy page
├── cookie-consent.php     # GDPR cookie consent banner
├── index.php              # Entry point (usually empty - not used for routing)
├── render-comments.php    # Comments section renderer (legacy wrapper)
├── download.php           # Download page template
├── download_file.php      # File download handler (no theme wrapper)
├── partials/              # Shared, reusable markup snippets
│   ├── card.php           # Post card (used by home + all listing templates)
│   ├── meta.php           # Author / date row for cards
│   ├── paginator.php      # Pagination wrapper
│   └── comments.php       # Comment list + "load more" section
└── lang/                  # Translation files
    ├── en.json            # English (always required - fallback language)
    ├── ar.json            # Arabic
    ├── zh.json            # Chinese
    ├── fr.json            # French
    ├── ru.json            # Russian
    ├── es.json            # Spanish
    └── id.json            # Indonesian
```

> **Note on `index.php`:** It is left in place for backward compatibility but the current Dispatcher/`ThemeRenderer` does **not** use it as a fallback template. Every route maps to a template with the route key's name (see [Section 5](#5-template-hierarchy)).

### Asset Directory Structure

```
assets/
├── css/                  # Stylesheets (sources + .min.css production versions)
│   ├── style.sea.css     # Main theme styles (source)
│   ├── style.sea.min.css # Minified production version
│   ├── custom.css        # Custom overrides (source)
│   ├── custom.min.css    # Minified custom CSS
│   ├── comment.css       # Comment section styling (source)
│   ├── comment.min.css   # Minified comment CSS
│   ├── cookie-consent.css # Cookie banner styling (source)
│   ├── cookie-consent.min.css # Minified cookie CSS
│   ├── privacy.css       # Privacy page styling (source)
│   ├── privacy.min.css   # Minified privacy CSS
│   ├── rtl.css           # RTL language support (source)
│   ├── rtl.min.css       # Minified RTL CSS
│   ├── sina-nav.css      # Sina navigation styles (source)
│   ├── sina-nav.min.css  # Minified sina-nav
│   ├── prism-override.css # Prism syntax highlighting overrides (source)
│   ├── prism-override.min.css # Minified Prism overrides
│   ├── not-found.min.css # 404 page styling (minified only)
│   ├── fontastic.min.css # Fontastic icon font styles (minified only)
│   ├── animate.min.css   # CSS animations (minified only)
│   └── fonts/            # Icon/custom font files (blog.eot, blog.woff, ...)
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
│   ├── jquery.cookie/    # jQuery cookie plugin
│   └── prism/            # Prism.js syntax highlighting
└── img/                  # Theme images (hero.jpg, placeholder.svg, favicon.ico, ...)
```

### File Purpose Summary

| File | Purpose | Dependencies |
|------|---------|--------------|
| `theme.ini` | Metadata (name, designer, directory) | None |
| `functions.php` | Loads the five `functions-*.php` modules + shared helpers | Core services, DB connection |
| `functions-i18n.php` | `t()`, `locale_url()`, `language_switcher()`, locale detection | JSON files in `lang/` |
| `functions-nav.php` | Menu tree building + rendering, URL conversion | `MenuViewModel`, `convert_menu_link()` |
| `functions-post.php` | Post/page retrieval, `prepare_post_card()`, URL builders | `FrontService`, models, DAOs |
| `functions-media.php` | Slideshow, galleries, thumbnails | Media model/DAO |
| `functions-comments.php` | Comment count, CSRF token, comment section renderer | Comment model/DAO |
| `header.php` | HTML head, nav, CSS loading | `functions.php` |
| `footer.php` | JS loading, footer, cookie consent | `header.php` |
| `home.php` | Homepage (hero, posts, gallery) | header/footer + `partials/card.php` |
| `single.php` | Post view + password protection | `ProtectedPostService`, `sidebar.php` |
| `page.php` | Static page view | header/footer |
| `category.php` | Category archive | `partials/card.php`, `partials/paginator.php`, `sidebar.php` |
| `tag.php` | Tag archive | `partials/card.php`, `partials/paginator.php`, `sidebar.php` |
| `archive.php` | Monthly archive | `partials/card.php`, `partials/paginator.php`, `sidebar.php` |
| `archives.php` | Archive index | header/footer |
| `blog.php` | Blog listing | `partials/card.php`, `partials/paginator.php`, `sidebar.php` |
| `search.php` | Search results page | `sidebar.php` |
| `sidebar.php` | Search, categories, tags, archives widgets | `SidebarViewModel`, `prepare_sidebar()` |
| `404.php` | Error page | header/footer |
| `privacy.php` | Privacy policy | header/footer |
| `cookie-consent.php` | GDPR banner | None (included by `footer.php`) |
| `partials/card.php` | Shared post card markup | `PostViewModel` |
| `partials/meta.php` | Author/date row for cards | Variables set by `card.php` |
| `partials/paginator.php` | Shared pagination wrapper | Pre-built pagination HTML string |
| `partials/comments.php` | Comment list + load-more | `render_comments_section()` capture |
| `render-comments.php` | Legacy comments renderer (kept for compatibility) | `partials/comments.php` |
| `download.php` | Download file info | header/footer |
| `download_file.php` | File download handler (no header/footer) | None |

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
>
> **Reference:** the bundled blog theme's `theme.ini` uses `theme_name = Bootstrap Blog`, `theme_designer = Ondrej Svetska`, and `theme_directory = blog`.

---

## 4. Template Loading Pattern (CRITICAL)

### The Golden Rule

**NEVER include `call_theme_header()` or `call_theme_footer()` in your template files.**

The core system (`ThemeRenderer`, driven by the `Dispatcher`) automatically loads header and footer:

```
Dispatcher → ThemeRenderer::render(routeKey) loads templates in this sequence:
  1. header.php                → Loads header.php automatically
  2. {routeKey}.php            → Loads the page template (home.php, single.php, etc.)
  3. footer.php                → Loads footer.php automatically
```

### Correct Template Format

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// NO call_theme_header() here - the system handles it
// Template content starts directly:
?>
<div class="container">
    <!-- Page content here -->
</div>
<?php
// NO call_theme_footer() here - the system handles it
```

### Incorrect (DO NOT USE)

```php
<?php
call_theme_header();  // WRONG - causes duplicate headers and "headers already sent" errors
?>
<div class="container">
    <!-- content -->
</div>
<?php
call_theme_footer();  // WRONG - causes duplicate footers
?>
```

### Why This Rule Exists

1. **"headers already sent" errors**: PHP cannot set HTTP headers (status codes, cookies) after output has started. The Dispatcher sets 404 status codes before template rendering. If a template emits output early (via header/footer includes), header modifications fail.
2. **Duplicate content**: Loading header/footer twice produces invalid HTML (two `<html>`, two `<head>`, duplicate CSS/JS).
3. **Consistent rendering**: The core system guarantees header → content → footer execution order.

---

## 5. Template Hierarchy

The Dispatcher compiles the route table (defined in `Bootstrap::defineRoutingRules()`) and renders the template whose filename matches the route key. There is **no** `index.php` fallback chain - each route maps directly to `{routeKey}.php`:

| Route Key | URL Pattern | Template Rendered | Notes |
|-----------|-------------|-------------------|-------|
| `home` | `/` | `home.php` | |
| `single` | `/post/{id}/{slug}` | `single.php` | Content existence validated first |
| `page` | `/page/{slug}` | `page.php` | Content existence validated first |
| `category` | `/category/{slug}` | `category.php` | Content existence validated first |
| `tag` | `/tag/{tag}` | `tag.php` | Content existence validated first |
| `archive` | `/archive/{mm}/{yyyy}` | `archive.php` | Content existence validated first |
| `archives` | `/archives` | `archives.php` | |
| `blog` | `/blog` and `/blog/*` | `blog.php` | |
| `search` | `/search` (permalinks ON) or `?q=` on app root (permalinks OFF) | `search.php` | Routed to `SearchController` first |
| `privacy` | `/privacy` | `privacy.php` | |
| `locale` | `/locale` | - | Routed to `LocaleController` (language switch) |
| `download` | `/download/{identifier}` | `download.php` | |
| `download_file` | `/download/{identifier}/file` | `download_file.php` | Bypasses header/footer (file stream) |
| 404 | any unmatched URL | `404.php` | Set by the Dispatcher via `ThemeRenderer::render404()` |

**Important:**
- The Dispatcher validates content existence **before** rendering. If content is not found, it sets a 404 status and renders `404.php` - it never falls through to the requested template.
- The `search`, `locale`, and `download_file` routes are special: they are handled by dedicated controllers/handlers and only some of them use the normal header/footer wrapper.
- HTMX themes (like `valdur`) can request a fragment instead of a full page; the Dispatcher detects that and renders a partial view.

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
$featured = featured_post();                              // Random headline post(s)
$sticky = sticky_page();                                   // Random sticky page(s)
$random = random_posts(0, 6);                              // Random posts for alternating layout
$latest = latest_posts(app_reading_setting()['post_per_page']); // Latest posts (posts-per-page setting)
$galleries = display_galleries(0, 4);                      // Gallery images
```

The latest-posts grid reuses the shared `partials/card.php` (via `prepare_post_card()`), so homepage cards look identical to category/tag/blog cards.

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
- Featured image via `get_post_thumbnail($post_img, $post_title, $img_alt)`
- Post title, author, date, comment count (escaped with `theme_escape_html()`)
- Content rendered by `ProtectedPostService::resolve()` (sanitized once)
- Tags, previous/next navigation
- Comments section (AJAX-loaded) when comments are open

**Password-protected posts:**
- The template shows a password form (not content) when `$show_password_form` is true
- AJAX unlock via `/api/v1/posts/{id}/unlock` endpoint
- Rate limiting: max 5 failed attempts per 15 minutes per IP
- Content decryption uses AES-256-CBC with a passphrase derived from the database

The protected/public decision is **owned by `Scriptlog\Service\ProtectedPostService`**, not by the template. The template only reads the result:

```php
// In single.php - the service decides whether to show content or the unlock form
$protectedPostService = class_exists('ProtectedPostService') ? new ProtectedPostService() : null;
$post_render = ($protectedPostService instanceof ProtectedPostService)
    ? $protectedPostService->resolve($retrieve_post, $_SESSION['unlocked_posts'] ?? [])
    : ['id' => $post_id, 'is_protected' => ($post_visibility === 'protected'),
       'is_unlocked' => false, 'show_password_form' => ($post_visibility === 'protected'),
       'content' => ''];
$post_content = $post_render['content'];
$show_password_form = $post_render['show_password_form'];
```

```php
// Rendering decision in the template body
<?php if ($show_password_form) : ?>
    <!-- unlock form with CSRF token, data-post-id, and AJAX error/loading boxes -->
<?php else : ?>
    <?= $post_content; ?>  <!-- already sanitized and safe -->
<?php endif; ?>
```

**Important template rules (remediation):**
- The template **never** calls `http_response_code()` or `exit()`. If the post is missing, it renders a plain "Post not found" message - the Dispatcher already validated content existence before the template ran.
- There is **no** `password-form.php` include anymore; the unlock form is inline in `single.php`.

**Key JS dependencies:**
- `assets/js/unlock-post.js` - handles AJAX unlock
- `assets/js/comment-submission.js` - handles AJAX comment posting
- `assets/js/load-comment.js` - loads comments dynamically

**Security notes:**
- Post data is fetched through `Scriptlog\Service\FrontService::getPublishedPost()` (via `front_service()`), not the deprecated `FrontHelper`.
- Admin edit flow auto-decrypts content via `decrypt_post_admin()`
- Passwords are verified against a bcrypt hash in the `post_password` column
- Never expose the passphrase to the frontend

### 6.3 page.php

Static pages display with:
- Featured image
- Page title and metadata
- Content with HTML filtering
- Tags display

### 6.4 category.php, tag.php, archive.php, blog.php

All listing templates share the same structure. They fetch entries, then render each one through the shared `partials/card.php` partial (which builds a `PostViewModel` via `prepare_post_card()`) and end with the shared `partials/paginator.php` and the sidebar:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Fetch entries + pagination string from the model/service layer
$category_result = function_exists('posts_by_category') ? posts_by_category($topicId) : [];
$entries = isset($category_result['entries']) ? $category_result['entries'] : "";
$pagination = isset($category_result['pagination']) ? $category_result['pagination'] : "";

$partial_dir = dirname(__FILE__) . '/partials/';
?>

<div class="container">
    <div class="row">
        <div class="posts-listing col-lg-8">
            <?php if (!empty($entries)) :
                foreach ($entries as $entry) :
                    // Escaped once at the boundary → PostViewModel
                    $post = function_exists('prepare_post_card') ? prepare_post_card($entry) : PostViewModel::fromPrepared([]);
                    $card_class = 'col-xl-6';
                    include $partial_dir . 'card.php';
                endforeach;
            endif; ?>

            <!-- Shared pagination partial -->
            <?php
            $pagination_html = $pagination;
            $pagination_aria_label = t('pagination.navigation');
            include $partial_dir . 'paginator.php';
            ?>
        </div>

        <?php include dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>
```

**Key points:**
- Every listing renders post cards through `partials/card.php` - the markup is identical on home, category, tag, archive, and blog pages.
- `prepare_post_card()` returns a `PostViewModel` whose fields are escaped exactly once. The card partial prints them directly - no double escaping.
- Pagination is a pre-built HTML string passed to `partials/paginator.php`, which wraps it in a Bootstrap `<nav>/<ul>` (and emits nothing when there is no pagination).

### 6.5 sidebar.php

The sidebar is populated by `prepare_sidebar()`, which returns an escaped `Scriptlog\Core\Theme\SidebarViewModel`:

```php
$sidebar = function_exists('prepare_sidebar') ? prepare_sidebar() : null;
```

Widgets include:
- **Search form**: AJAX-powered with a hidden CSRF token, `aria-live` results container, a clear (×) button, and a permalink-aware non-JS fallback (the `search_action` is built with `theme_search_url()` - `/search` when permalinks are ON, app root `?q=` when OFF)
- **Latest posts**: 5 most recent posts with thumbnails (via `$sidebar->latestPosts()`)
- **Categories**: List with post counts (via `$sidebar->categories()`)
- **Archives**: Monthly archive links with post counts (via `$sidebar->archives()`)
- **Tags**: Tag cloud (via `$sidebar->tags()`)

```php
// Search widget essentials - note the CSRF token and aria-live containers
<form action="<?= theme_escape_html($search_action); ?>" method="get" class="search-form" id="ajax-search-form"
      role="search" aria-label="<?= t('sidebar.search.title'); ?>">
    <input type="search" id="search-keyword" name="q" class="search-input"
           placeholder="<?= t('sidebar.search.placeholder'); ?>" autocomplete="off">
    <button type="submit" class="search-submit" aria-label="<?= t('sidebar.search.submit'); ?>">...</button>
    <button type="button" class="search-clear" id="search-clear" aria-label="<?= t('sidebar.search.clear'); ?>" hidden>×</button>
    <div id="search-results" class="search-results" aria-live="polite" aria-atomic="true"></div>
    <div id="search-error" class="search-error" aria-live="assertive"></div>
    <input type="hidden" id="search-csrf" name="csrf" value="<?= block_csrf(); ?>">
</form>
```

### 6.6 404.php

Simple error page:
- 404 display
- "Page not found" message
- Back to Home link

### 6.7 privacy.php

Privacy policy page - either database-driven or static fallback:
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

- `render-comments.php`: Legacy wrapper that renders the comments section HTML. It is still loaded by `functions.php`-loaded modules but the actual markup now lives in `partials/comments.php`, which `render_comments_section()` captures via `ob_start()`/`ob_get_clean()`.

### 6.11 archives.php

Archive index page listing all archive months grouped by year (see the example at the end of Section 6.12):

### 6.12 search.php

The search results page displays matches from the `SearchFinder` engine:

- **Route (permalinks ON)**: `/search?q=keyword` (full page) - the Dispatcher routes the `/search` path to `SearchController`
- **Route (permalinks OFF)**: `?q=keyword` on the app root (e.g. `https://example.com/?q=keyword`) - the query-string router (`HandleRequest::deliverQueryString()`) dispatches the `q` key to `SearchController`
- **AJAX**: `/api/v1/search?q=keyword` (sidebar widget - always path-based, independent of the permalink setting)
- **Best practice**: never hard-code the `/search` path. Use the `theme_search_url()` helper (see Section 7.3) so the form action matches whichever URL scheme is active.
- **Data source**: four `$GLOBALS` entries set by `SearchController` before the template renders:

| Global | Contents |
|--------|----------|
| `$GLOBALS['search_results']` | Array with `results` (list of result objects) and `totalRows` (int). Each result object has `ID`, `post_title`, `post_slug`, `post_content`, `post_type`, `post_date`. Also set `error` when the search failed. |
| `$GLOBALS['search_keyword']` | The raw search keyword string |
| `$GLOBALS['search_pagination']` | Array with `page`, `totalPages`, and `html` (pre-built pagination HTML) |
| `$GLOBALS['search_rate_limited']` | `true` when the IP is rate limited (search temporarily disabled) |

**Key template setup (the exact lines in the blog theme):**

```php
$searchResults = isset($GLOBALS['search_results']) ? $GLOBALS['search_results'] : [];
$searchKeyword = isset($GLOBALS['search_keyword']) ? theme_escape_html($GLOBALS['search_keyword']) : '';
$searchPagination = isset($GLOBALS['search_pagination']) ? $GLOBALS['search_pagination'] : [];
$searchRateLimited = isset($GLOBALS['search_rate_limited']) ? (bool)$GLOBALS['search_rate_limited'] : false;

$results = isset($searchResults['results']) ? $searchResults['results'] : [];
$totalRows = isset($searchResults['totalRows']) ? (int)$searchResults['totalRows'] : 0;
$hasError = isset($searchResults['error']);
$currentPage = isset($searchPagination['page']) ? (int)$searchPagination['page'] : 1;
$totalPages = isset($searchPagination['totalPages']) ? (int)$searchPagination['totalPages'] : 0;
$paginationHtml = isset($searchPagination['html']) ? $searchPagination['html'] : '';
$searchAction = function_exists('theme_search_url') ? theme_search_url() : (rewrite_status() === 'yes' ? (string)app_url() . '/search' : (string)app_url() . '/');
```

**Rendering states handled by the template (in order):**
1. **Rate limited** → warning alert with `search.rate_limited`
2. **Error** → warning alert with `search.error`
3. **Results found** → result list + pagination (`search.page_x_of_y`, `search.read_more`)
4. **Keyword given, no results** → empty state (`search.no_results_title`, `search.try_different_keywords`)
5. **No keyword** → prompt (`search.enter_keyword_title`, `search.enter_keyword`)

**Key functions used inside the loop:**

```php
theme_page_url(['ID' => $itemId, 'post_slug' => $item->post_slug])  // page URL (permalinks-aware)
theme_post_url(['ID' => $itemId, 'post_slug' => $item->post_slug])  // post URL (permalinks-aware)
paragraph_l2br(safe_html(paragraph_trim($item->post_content)))      // safe excerpt
make_date($item->post_date)                                         // formatted date
```

**Key i18n keys used:**
- `search.title`, `search.found_results` (`%count%`, `%keyword%`), `search.no_results` (`%keyword%`)
- `search.rate_limited`, `search.error`
- `search.page_x_of_y` (`%page%`, `%total%`), `search.read_more`, `search.read_more_aria`
- `search.no_results_title`, `search.try_different_keywords`
- `search.enter_keyword_title`, `search.enter_keyword`
- `search.type.post`, `search.type.page` (result type badges)

**AJAX search flow (sidebar widget):**
1. User types in search input - `search.js` fires a `GET /api/v1/search?q=keyword&type=all` request with a 300ms debounce
2. `SearchApiController` transforms results to JSON with id, title, slug, excerpt, type, date, url
3. Up to 10 inline results shown in the `aria-live` dropdown; the "View all N results" link routes to `theme_search_url()` + `?q=keyword` (i.e. `/search?q=keyword` with permalinks ON, `?q=keyword` on the app root with permalinks OFF - the URL is read from `scriptlog_vars.search_url`, exposed by `header.php`)
4. The request includes the hidden CSRF token from `#search-csrf`
5. Non-JS fallback: the form submits via GET to the `theme_search_url()` action (permalinks-aware), and the Dispatcher routes to `SearchController`

**Key JS dependencies:**
- `assets/js/search.js` - jQuery AJAX autocomplete with debounce, XSS-safe rendering, clear-button handling

**Archives example** (from `archives.php` - a grouped month/year listing):

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

## 7. Theme Functions (functions.php & functions-*.php) Complete Reference

> **Where do these functions live?** `functions.php` is a thin loader - it simply includes the five helper modules (`functions-i18n.php`, `functions-nav.php`, `functions-post.php`, `functions-media.php`, `functions-comments.php`). Every function below is guarded with `function_exists()` so the modules can be loaded safely more than once. You can call all of them as if they lived in a single `functions.php`.
>
> The blog theme's `functions.php` also loads two shared building blocks before the modules:
> - `theme_escape_html()` (from `lib/utility/theme-escape.php`) - the single output-escaping boundary used everywhere in the theme.
> - The shared ViewModel layer (`Scriptlog\Core\Theme\*`) via `ThemeHelper::loadShared()` - the typed, already-escaped data objects templates render (`PostViewModel`, `SidebarViewModel`, `MenuViewModel`, ...).

### 7.1 i18n Functions (`functions-i18n.php`)

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `t()` | `(string $key, array $params = []): string` | Translate a string. Params replace `%param%` placeholders (e.g. `t('search.found_results', ['count' => 3, 'keyword' => 'php'])`). Missing keys fall back to `en`, then to the key itself. | Translated string |
| `locale_url()` | `(string $path = '', ?string $locale = null): string` | Generate URL with locale prefix (when permalinks + prefix enabled) | Full URL string |
| `get_locale()` | `(): string` | Get current frontend locale | e.g. `'en'`, `'ar'`, `'id'` |
| `available_locales()` | `(): array` | Get all available locales | `['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id']` |
| `is_rtl()` | `(): bool` | Check if current locale is RTL | `true` for Arabic |
| `get_html_dir()` | `(): string` | Get HTML `dir` attribute value | `'ltr'` or `'rtl'` |
| `language_switcher()` | `(array $args = []): string` | Generate language switcher HTML | HTML dropdown markup |
| `get_language_name()` | `(string $locale, bool $native = true): string` | Get language display name | e.g. `'English'` or `'العربية'` |
| `get_all_language_names()` | `(): array` | Get all language display names | Locale → name map |
| `detect_browser_locale()` | `(): string` | Detect locale from the `Accept-Language` header | Locale code, falls back to `'en'` |
| `load_theme_translations()` | `(string $locale): array` | Load (and cache) a locale's JSON dictionary | Array of key → value |
| `reset_i18n_cache()` | `(): void` | Clear the translation cache (used in tests) | - |
| `is_locale_prefix_enabled()` | `(): bool` | Whether the locale URL prefix feature is on | `true`/`false` |
| `get_default_locale()` | `(): string` | Get the site's default language | e.g. `'en'` |

### 7.2 Model Initialization Functions (`functions-post.php`)

| Function | Description | Returns |
|----------|-------------|---------|
| `initialize_post()` | Initialize `PostModel` singleton | `PostModel` instance |
| `initialize_page()` | Initialize `PageModel` singleton | `PageModel` instance |
| `initialize_comment()` | Initialize `CommentModel` singleton | `CommentModel` instance |
| `initialize_archive()` | Initialize `ArchivesModel` singleton | `ArchivesModel` instance |
| `initialize_topic()` | Initialize `TopicModel` singleton | `TopicModel` instance |
| `initialize_tag()` | Initialize `TagModel` singleton | `TagModel` instance |
| `initialize_gallery()` | Initialize `GalleryModel` singleton | `GalleryModel` instance |

> For new code, prefer `Scriptlog\Service\FrontService` (via `front_service()`) over directly initializing models. It wraps these lookups with a clean, tested API.

### 7.3 Post Retrieval & Preparation Functions (`functions-post.php`)

| Function | Description | Returns |
|----------|-------------|---------|
| `featured_post()` | Get random post with `post_headlines = 'Y'` | Array or null |
| `sticky_page()` | Get random published page (`post_type = 'page'`) | Array or null |
| `random_posts(int $start, int $end)` | Get random published posts in range | Array of posts |
| `latest_posts(int $limit, $position)` | Get latest posts with offset | Array of posts |
| `retrieve_blog_posts()` | Get all published blog posts | Array of posts |
| `retrieve_detail_post(int $id)` | Get single post by ID (includes protected) | Array or null |
| `posts_by_archive(array $values)` | Get posts by archive month/year | Array with `entries` + `pagination` |
| `archive_index()` | Get all archive months with post counts | Array of archives |
| `posts_by_tag(string $tag)` | Get posts matching a tag | Array of posts |
| `searching_by_tag(string $tag)` | Full-text search by tag | Array of posts |
| `posts_by_category(int $topicId)` | Get posts by category/topic ID | Array with `entries` + `pagination` |
| `retrieve_page(mixed $arg, bool $rewrite)` | Get page by ID or slug | Array or null |
| `retrieve_archives()` | Get archives for sidebar widget | Array of archives |
| `prepare_post_card(array $entry)` | **NEW** - normalize one post row into an escaped `PostViewModel` (used by `partials/card.php`) | `PostViewModel` |
| `prepare_page(array $entry)` | Normalize a page row into an escaped `PageViewModel` | `PageViewModel` |
| `prepare_archive(array $entry)` | Normalize an archive row into an escaped `ArchiveViewModel` | `ArchiveViewModel` |
| `prepare_sidebar()` | Build the escaped `SidebarViewModel` (latest posts, categories, archives, tags, search action) | `SidebarViewModel` |
| `format_topics($topics_data)` | Format topic data for display | Formatted array |
| `nothing_found()` | Display a "no posts found" message | HTML string |
| `theme_post_url(array $row)` | **NEW** - permalink-aware post URL from `['ID' => int, 'post_slug' => string]` | URL string |
| `theme_page_url(array $row)` | **NEW** - permalink-aware page URL | URL string |
| `theme_topic_url(array $row)` | **NEW** - permalink-aware category URL | URL string |
| `theme_tag_url(string $tag)` | **NEW** - permalink-aware tag URL | URL string |
| `theme_archive_url(string $month, string $year)` | **NEW** - permalink-aware archive URL | URL string |
| `theme_search_url()` | **NEW** - permalink-aware search page URL: `{base}/search` when permalinks are ON, `{base}/` (query-string `?q=`) when OFF. Memoized per request. Use it for the full-page search form action (`search.php`), the sidebar `search_action` (`functions-post.php` / `sidebar.php`), and the `search_url` exposed to JS in `header.php` (`scriptlog_vars.search_url` consumed by `search.js` for the "View all results" link) | Raw (unescaped) URL string |
| `theme_month_name(string $month)` | **NEW** - month number → local month name | String |

### 7.4 Navigation Functions (`functions-nav.php`)

> **Where things live:** `theme_navigation()` is defined in `lib/utility/theme-navigation.php`. The `previous_post()`, `next_post()`, `link_tag()`, and `link_topic()` helpers are defined in `functions-post.php` (they render post navigation links) and are listed here because they are navigation-related.

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `front_navigation()` | `(int $parent, array $menu): string` | **NEW behavior** - takes the raw `theme_navigation()` output, converts every item into a `MenuViewModel` (escaping once), builds the recursive tree with `build_menu_tree()`, and renders it | HTML string |
| `build_menu_tree()` | `(array $items, array $parents, int $rootId = 0): array` | **NEW** - build a recursive `MenuViewModel` tree from flat items/parents | `MenuViewModel[]` |
| `render_menu_tree()` | `(array $nodes): string` | **NEW** - render a `MenuViewModel` tree to dropdown HTML | HTML string |
| `theme_navigation()` | `(string $visibility = 'public'): array` | Get menu items filtered by locale and visibility (lives in `lib/utility/theme-navigation.php`) | Array with `items` + `parents` |
| `convert_menu_link()` | `(string $link, bool $permalinkEnabled): string` | Convert menu link between SEO-friendly and query-string format | Converted URL string |
| `request_path()` | `(): object` | Get the current request path object | `RequestPath` object |
| `retrieve_site_url()` | `(): string` | Get site base URL from config | URL string |
| `link_tag()` | `(int $postId): string` | Generate tag links for a post | HTML string |
| `link_topic()` | `(int $postId): string` | Generate category links for a post | HTML string |
| `previous_post()` | `(int $postId): string` | Get previous post navigation link | HTML string |
| `next_post()` | `(int $postId): string` | Get next post navigation link | HTML string |

### 7.5 Utility & Comments Functions (`functions-comments.php`, `functions-media.php`, `functions-post.php`)

> **Where things live:** comment helpers (`total_comment()`, `block_csrf()`, `render_comments_section()`) are in `functions-comments.php`; media helpers (`get_slideshow()`, `display_galleries()`, `get_download_page_data()`, `get_post_thumbnail()`) are in `functions-media.php`; topic/tag helpers (`retrieves_topic_simple()`, `retrieves_topic_prepared()`, `sidebar_topics()`, `retrieve_tags()`) are in `functions-post.php`; and `make_date()` is a shared core helper in `lib/utility/make-date.php`.

| Function | Signature | Description | Returns |
|----------|-----------|-------------|---------|
| `total_comment()` | `(int $postId): array` | Count approved comments for a post. **Returns an array** `['total' => int]`, not a plain integer. Use `$data['total']` | `['total' => int]` |
| `block_csrf()` | `(): string` | Generate CSRF token for comment/search forms | Hidden input HTML |
| `render_comments_section()` | `(int $postId, int $offset = 0): string` | Render comments section HTML by capturing `partials/comments.php` | HTML string |
| `get_slideshow($limit = 5)` | `($limit = 5): array` | Get posts with media for the homepage slideshow | Array of posts |
| `display_galleries()` | `(int $start, int $limit): array` | Get gallery media items | Array of media |
| `get_download_page_data()` | `(string $identifier): array` | Get download page data by UUID identifier | Array |
| `get_post_thumbnail()` | `(string $post_img, string $post_title, string $img_alt = ''): string` | Render an optimized post thumbnail image | HTML string |
| `retrieves_topic_simple()` | `(int $postId): array` | Get topic IDs for a post | Array of topic IDs |
| `retrieves_topic_prepared()` | `(int $postId): array` | Get prepared topic data for a post | Array of topics |
| `sidebar_topics()` | `(): array` | Get active topics with post counts | Array of topics |
| `retrieve_tags()` | `(): array` | Get all tags from posts | Array of unique tags |
| `make_date()` | `(string $timestamp): string` | Format date for display (e.g. "July 26, 2026"). ⚠ Display only - do NOT use in admin form `<input>` values; pass raw `Y-m-d H:i:s` instead. | Formatted date string |

### 7.6 Shared Core Helpers (not theme files, but used everywhere)

These are loaded by the core, not defined in the theme, yet you will use them constantly in templates:

| Function | Location | Purpose |
|----------|----------|---------|
| `theme_escape_html()` | `lib/utility/theme-escape.php` | **The** output-escaping boundary. Use it for every dynamic string. |
| `front_service()` | `lib/utility/front-service.php` | Returns the shared `Scriptlog\Service\FrontService` instance (replaces `FrontHelper`) |
| `theme_dir()` | `lib/utility/theme-caller.php` | Current theme directory URL (e.g. for asset paths) |
| `app_url()` / `app_sitename()` / `app_tagline()` | `lib/utility/app-info.php` | Site URL, name, tagline from settings |
| `get_ip_address()` | core utility | Client IP address |
| `front_paginator()` | `lib/utility/front-paginator.php` | Returns a `Paginator` instance for building pagination HTML |

---

## 8. Navigation & i18n URL Compatibility

### Overview

The navigation system must work seamlessly with both URL schemes:

| Permalink Status | Menu Link Format | Language Switcher Format |
|-----------------|------------------|--------------------------|
| **Disabled** | Query string (`?p=1`, `?pg=1`, `?cat=1`, `?a=032025`) | `?switch-lang=XX&redirect=...` |
| **Enabled** | SEO-friendly (`/post/1/slug`, `/page/slug`, `/category/slug`) | `locale_url()` with proper prefix |

### convert_menu_link() Logic

Located in `functions-nav.php`, this function converts links dynamically:

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
    <a href="<?= theme_escape_html($langUrl); ?>"
       class="dropdown-item <?= (get_locale() === $locale) ? 'active' : ''; ?>">
        <?= theme_escape_html(get_language_name($locale, true)); ?>
    </a>
<?php endforeach; ?>
```

### theme_navigation() Locale Filtering

```php
function theme_navigation($visibility)
{
    $currentLocale = function_exists('get_locale') ? get_locale() : 'en';

    $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility,
                   parent_id, menu_sort, menu_locale
            FROM tbl_menu
            WHERE menu_status = 'Y'
              AND menu_visibility = ?
              AND (menu_locale = ? OR menu_locale IS NULL OR menu_locale = '')
            ORDER BY menu_sort ASC, menu_label";

    // ... execute query and return ['items' => [...], 'parents' => [...]]
}
```

---

## 9. Asset Management

### 9.1 CSS Files

All CSS uses `media="print" onload="this.media='all'"` for non-blocking loading with a `<noscript>` fallback. In the blog theme, every asset link also carries an SRI `integrity` hash and `crossorigin="anonymous"`.

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
| `prism.css` | Prism.js syntax highlighting base styles | Always |
| `prism-override.min.css` | Theme tweaks for code blocks | Always |
| `rtl.min.css` | RTL layout overrides | Only when `is_rtl()` |

### 9.2 JavaScript Files

All scripts after jQuery use the `defer` attribute for non-blocking execution. jQuery loads synchronously (no `defer`). In the blog theme, every `<script>` tag carries an SRI `integrity` hash and `crossorigin="anonymous"`, and inline `<script>` blocks include a CSP `nonce` (from the `CSP_NONCE` constant) - required by the site's Content-Security-Policy header.

| File | Purpose | Load Method |
|------|---------|-------------|
| `jquery.min.js` | DOM manipulation, AJAX foundation | Synchronous (required first) |
| `popper.min.js` (vendor) | Bootstrap dropdown positioning | `defer` |
| `bootstrap.min.js` | Bootstrap UI components | `defer` |
| `jquery.cookie.js` | Cookie read/write | `defer` |
| `jquery.fancybox.min.js` | Image gallery lightbox | `defer` |
| `prism.js` (vendor) | Client-side syntax highlighting | `defer` |
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
| **Prism.js** | 1 | `prism.css`, `prism-override.min.css`, `prism.js` | Client-side syntax highlighting for code blocks |

### 9.4 Load Order (footer.php)

JavaScript load order is critical for proper functionality. jQuery must load **synchronously** (no `defer`). All subsequent scripts use `defer` to preserve execution order without blocking page render. The blog theme also adds `integrity`/`crossorigin` (SRI) on every script and renders inline scripts with the CSP nonce:

```html
<!-- jQuery MUST load first (synchronous) -->
<script src="assets/vendor/jquery/jquery.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
<!-- Popper.js MUST load before Bootstrap JS -->
<script src="assets/vendor/popper.js/umd/popper.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Bootstrap JS -->
<script src="assets/vendor/bootstrap/js/bootstrap.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Cookie plugin (used by other scripts) -->
<script src="assets/vendor/jquery.cookie/jquery.cookie.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Fancybox lightbox -->
<script src="assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Prism.js syntax highlighting -->
<script src="assets/vendor/prism/prism.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Theme frontend core -->
<script src="assets/js/front.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Animation libraries (order: marquee → pause → easing) -->
<script src="assets/js/jquery.marquee.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/jquery.pause.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/jquery.easing.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Feature-specific -->
<script src="assets/js/comment-submission.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/load-comment.min.js?v=1.2" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/validator.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/wow.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/sina-nav.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/cookie-consent.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/search.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<script src="assets/js/unlock-post.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- RTL support (conditional) -->
<script src="assets/js/rtl.min.js" integrity="sha384-..." crossorigin="anonymous" defer></script>
<!-- Cookie consent banner partial (included at the very end) -->
<?php if (file_exists(__DIR__ . '/cookie-consent.php')) : ?>
    <?php include __DIR__ . '/cookie-consent.php'; ?>
<?php endif; ?>
```

> **The `header.php` also defines `scriptlog_vars`** - a global JS object exposing `api_url`, `site_url`, `theme_dir`, and a `search` object with translated strings for the AJAX search widget. Inline scripts that read it must include the `nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : ''; ?>"` attribute.

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
    string $media_filename,
    string $size = 'thumbnail',    // 'thumbnail', 'medium', 'large'
    bool $image_thumb = true,
    string $alt = '',
    string $class = 'img-fluid',
    bool $fetchpriority = false,   // true for hero/LCP images
    string $decoding = 'auto',
    string $loading = 'auto'       // 'lazy'|'eager'|'auto' (no attribute when 'auto')
): string

// Hero image with fetchpriority="high" (LCP optimization)
// Note the real signature: ($media_filename, $fallback_url = '', $alt = '')
invoke_hero_image(string $media_filename, string $fallback_url = '', string $alt = ''): string

// Gallery image with lazy loading
invoke_gallery_image(string $media_filename, string $alt = ''): string
```

### Image Display in Templates

```php
<!-- Hero/LCP image - high priority loading -->
<?= invoke_hero_image($post['media_filename'], '', $post['post_title']); ?>

<!-- Responsive image with WebP fallback -->
<?= invoke_responsive_image($post['media_filename'], 'medium', true, $post['post_title']); ?>

<!-- Simple featured image (thumbnail) -->
<?= invoke_frontimg($post['media_filename']); ?>

<!-- Gallery images with lightbox -->
<a href="<?= app_url() . '/' . APP_IMAGE . rawurlencode($image['media_filename']); ?>"
   data-fancybox="gallery" data-caption="<?= theme_escape_html($image['media_caption']); ?>">
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

1. **Current locale JSON**: `public/themes/blog/lang/{locale}.json` (fast, file-based, cached in memory)
2. **English fallback**: if a key is missing from the current locale, `lang/en.json` is used
3. **Key as-is**: if the key exists in neither, the key string itself is returned

So every locale's dictionary may be a partial translation - missing keys automatically display in English.

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

<!-- With placeholder replacement (server-side: %param% notation) -->
<p><?= t('search.found_results', ['count' => 3, 'keyword' => $searchKeyword]); ?></p>

<!-- HTML direction for RTL -->
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">

<!-- Conditional RTL CSS loading -->
<?php if (is_rtl()): ?>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.min.css">
<?php endif; ?>
```

**Two different interpolation formats - don't mix them up:**

| Format | Where it's used | Example |
|--------|-----------------|---------|
| `%param%` | PHP side - `t()` replaces `%param%` in the JSON string | `search.found_results` → `Found %count% result(s) for "%keyword%"` |
| `{{placeholder}}` | JS side - the language switcher label is rendered by JavaScript | `header.nav.language_switch` → `Language: {{language}}` |

### Translation Key Dictionary

All keys below exist in `lang/en.json` (117 keys in the blog theme). Keys missing from another locale's JSON automatically fall back to English.

#### Navigation (header.nav.*, footer.navigation.*)
| Key | English |
|-----|---------|
| `header.nav.home` | Home |
| `header.nav.blog` | Blog |
| `header.nav.about` | About |
| `header.nav.contact` | Contact |
| `header.nav.search` | Search |
| `header.nav.language_switch` | Language: {{language}} (JS-rendered) |
| `footer.navigation.home` / `.blog` / `.about` / `.contact` / `.privacy` | Footer menu labels |

#### Search Page (search.*)
| Key | English |
|-----|---------|
| `search.title` | Search |
| `search.found_results` | Found %count% result(s) for "%keyword%" |
| `search.no_results` | No results found for "%keyword%" |
| `search.enter_keyword` | Please enter a search keyword to find content. |
| `search.enter_keyword_title` | Search Our Blog |
| `search.no_results_title` | No Results Found |
| `search.try_different_keywords` | No results found. Please try different keywords. |
| `search.read_more` | Read More |
| `search.read_more_aria` | Read more about %s |
| `search.rate_limited` | Too many search requests. Please wait a moment and try again. |
| `search.error` | Search is temporarily unavailable. Please try again later. |
| `search.page_x_of_y` | Page %page% of %total% |
| `search.pagination_navigation` | Search results pages |
| `search.page_prev` / `search.page_next` | Previous page / Next page |
| `search.type.post` | Article |
| `search.type.page` | Page |
| `search.widget.*` | Sidebar widget: count, no_results, view_all, loading, error |

#### Sidebar (sidebar.*)
| Key | English |
|-----|---------|
| `sidebar.search.title` | Search |
| `sidebar.search.placeholder` | What are you looking for? |
| `sidebar.search.submit` | Search |
| `sidebar.search.clear` | Clear search |
| `sidebar.search.hint` | Results appear live as you type. Press Enter for the full results page. |
| `sidebar.latest_posts.title` | Latest Posts |
| `sidebar.categories.title` | Categories |
| `sidebar.archives.title` | Archives |
| `sidebar.tags.title` | Tags |

#### Home (home.*)
| Key | English |
|-----|---------|
| `home.hero.discover_more` | Discover More |
| `home.hero.admin_panel` | Go to administrator panel |
| `home.hero.scroll_down` | Scroll Down |
| `home.intro.welcome` | Welcome to ScriptLog |
| `home.intro.description` | Your entryway to a personal blog |
| `home.latest_posts.title` | Latest from the blog |
| `home.divider.view_more` | View More |

#### Single Post (single.*, form.*, button.*, visibility.*, protected.*, status.*)
| Key | English |
|-----|---------|
| `single.comment.leave_reply` | Leave a comment |
| `single.comment.label` | Type your comment |
| `single.comment.placeholder` | Enter your comment |
| `single.comment.submit` | Submit Comment |
| `form.name.label` | Name |
| `form.email.label` | Email (will not be published) |
| `form.password` | Password |
| `button.unlock` | Unlock |
| `visibility.password` | Password Protected |
| `protected.post.description` | This post is password protected. Enter the password to view its content. |
| `status.loading` | Loading... |
| `error.wrong_password` | Incorrect password. Please try again. |

#### Footer & Misc
| Key | English |
|-----|---------|
| `footer.copyright` | All rights reserved |
| `footer.navigation.aria_label` | Footer Navigation |
| `pagination.previous` / `pagination.next` | Previous / Next |
| `post.by` / `post.on` / `post.read_more` / `post.share` | Post meta labels |
| `category.uncategorized` | Uncategorized |

#### Cookie Consent (cookie_consent.*)
| Key | English |
|-----|---------|
| `cookie_consent.banner.title` | We value your privacy |
| `cookie_consent.banner.description` | uses cookies to enhance your browsing experience. |
| `cookie_consent.buttons.accept` | Accept All |
| `cookie_consent.buttons.reject` | Reject All |
| `cookie_consent.buttons.learn_more` | Learn More |
| `cookie_consent.privacy.link` | Privacy Policy |
| `cookie_consent.settings` | Cookie Settings |

#### 404 & Privacy
| Key | English |
|-----|---------|
| `404.title` | 404 |
| `404.message` | The page you are looking for was not found. |
| `404.back_home` | Back to Home |
| `privacy.page_title` | Privacy Policy |
| `privacy.last_updated` | Last updated |
| `privacy.information_we_collect` / `privacy.how_we_use` / `privacy.data_security` / `privacy.your_rights` / `privacy.contact_us` | Privacy page sections |

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
  "search.found_results": "Found %count% result(s) for \"%keyword%\"",
  "footer.copyright": "All rights reserved"
}
```

> Placeholder names in the JSON use `%param%` (no leading `$`). `t()` replaces them with the values you pass as the `$params` array (e.g. `t('search.found_results', ['count' => 5, 'keyword' => 'php'])`).

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

**There is one escaping boundary in the remediated themes: `theme_escape_html()`.** Use it for every dynamic string in a template. Never write your own `htmlspecialchars()` calls, and avoid mixing escape helpers.

```php
// CORRECT - escape all dynamic output (plain text) with the single boundary
<?= theme_escape_html($post['post_title']); ?>
<a href="<?= theme_escape_html($postUrl); ?>"><?= theme_escape_html($post['post_title']); ?></a>

// CORRECT - content that must keep its HTML is sanitized on the way in,
// then printed as-is (trusted HTML). Never escaped a second time.
<?= $post_content; ?>   <!-- already sanitized by htmLawed() in the service layer -->

// CORRECT - post excerpt (pre-sanitized by paragraph_trim)
<?= paragraph_l2br(safe_html(paragraph_trim($post['post_content']))); ?>
```

**The `htmlout()` double-encoding pitfall (for legacy code):** `htmlout()` chains `safe_html()` + `escape_html()`. Laminas' `escape_html()` calls `htmlspecialchars()` without `double_encode=false`, so valid entities like `&quot;` get re-encoded to `&amp;quot;`. Do not use `htmlout()` on content already stripped of tags - use `safe_html()` (as in the excerpt example above). New themes should simply use `theme_escape_html()` everywhere and only ever call `safe_html()` on pre-sanitized content.

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
| Rate limiting | 5 attempts/15 min per IP | Prevents brute force |
| Admin decryption | `decrypt_post_admin()` | Admin bypass without password |
| Frontend resolution | `ProtectedPostService::resolve()` | Decides content vs. unlock form; sanitizes decrypted content once |

The `ProtectedPostService` runs the decryption pipeline (double `html_entity_decode`, style strip, `htmLawed` sanitize) and returns a ready-to-print `content` string plus a `show_password_form` flag. Templates just render the result and must never expose the passphrase.

### 12.5 Cookie Consent & GDPR

The cookie consent banner must:
- Be displayed on first visit (no `cookie_consent` cookie)
- Offer Accept, Reject, and Learn More options
- Link to privacy policy page
- Track consent via API endpoint
- Not set analytics/marketing cookies without consent

### 12.6 Security Checklist for Theme Development

- [ ] All PHP files have `defined('SCRIPTLOG') || die()` guard
- [ ] All dynamic output uses `theme_escape_html()` - the single escaping boundary; use `safe_html()` for content pre-sanitized by `paragraph_trim()` (never chain two escape helpers)
- [ ] All forms include CSRF token via `block_csrf()` (including the AJAX search form)
- [ ] User-submitted content is sanitized with `htmLawed()` in the service layer, not in templates
- [ ] No database queries in templates (use the `functions-*.php` helpers / `FrontService`)
- [ ] No direct `include`/`require` of files from `$_GET` parameters
- [ ] Password-protected posts are resolved by `ProtectedPostService`; never expose the passphrase to the frontend
- [ ] Cookie consent banner is GDPR-compliant
- [ ] No `http_response_code()` or `exit()` in templates (Dispatcher/ThemeRenderer handles status codes)
- [ ] Theme does not expose absolute server paths
- [ ] Inline `<script>` blocks carry the CSP `nonce` (`<?= defined('CSP_NONCE') ? CSP_NONCE : ''; ?>`)

---

## 13. Creating a Custom Theme - Step-by-Step

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

**`functions.php` is a thin loader** - don't put all your code in it. Copy the structure from `public/themes/blog/functions.php`, which loads the shared helpers plus your own module files:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// 1. Load the shared escaping boundary so every template uses the same helper
if (!function_exists('theme_escape_html')) {
    require_once dirname(__DIR__, 3) . '/lib/utility/theme-escape.php';
}

// 2. Load the shared ViewModel layer (PostViewModel, SidebarViewModel, ...)
if (!class_exists('Scriptlog\Core\Theme\PostViewModel', false)) {
    require_once dirname(__DIR__, 3) . '/lib/core/Theme/ThemeHelper.php';
    Scriptlog\Core\Theme\ThemeHelper::loadShared();
}

// 3. Your own helper modules (each keeps function_exists() guards internally)
require_once dirname(__FILE__) . '/functions-i18n.php';
require_once dirname(__FILE__) . '/functions-nav.php';
require_once dirname(__FILE__) . '/functions-post.php';
require_once dirname(__FILE__) . '/functions-media.php';
require_once dirname(__FILE__) . '/functions-comments.php';
```

A simple `functions-post.php` module looks like this (note the `function_exists()` guard):

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

if (!function_exists('latest_posts')) {
    function latest_posts(int $limit = 5, int $position = 0): array
    {
        $postModel = initialize_post();
        return $postModel->getLatestPosts($limit, $position);
    }
}

if (!function_exists('prepare_post_card')) {
    function prepare_post_card(array $entry)
    {
        // Normalize once into an escaped PostViewModel (see blog theme)
        return \Scriptlog\Core\Theme\ThemeHelper::factory()->makePostFromPrepared($entry);
    }
}
```

> **Rule of thumb:** if a function renders markup, it goes in a `functions-*.php` module. If it reads data, prefer calling `front_service()` (`Scriptlog\Service\FrontService`) instead of hand-rolling model lookups.

### Step 4: Create header.php

```html
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
require dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html>
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= theme_escape_html($pageTitle ?? 'My Blog'); ?></title>

    <!-- Non-blocking CSS loading pattern (add SRI integrity + crossorigin in production) -->
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.min.css"></noscript>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.min.css"></noscript>

    <?php if (is_rtl()): ?>
    <link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.min.css">
    <?php endif; ?>
    <link rel="shortcut icon" href="<?= theme_dir(); ?>assets/img/favicon.ico">

    <!-- scriptlog_vars - the global JS config used by search.js and other scripts -->
    <script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : ''; ?>">
        var scriptlog_vars = {
            api_url: '<?= app_url(); ?>/api/v1',
            site_url: '<?= app_url(); ?>',
            theme_dir: '<?= theme_dir(); ?>'
        };
    </script>
</head>
<body>
<!-- Skip link for keyboard users -->
<a class="skip-link" href="#main-content"><?= t('skip_to_content'); ?></a>
<header role="banner">
<nav class="navbar navbar-expand-lg" role="navigation" aria-label="<?= t('nav.main_navigation'); ?>">
    <div class="container">
        <a class="navbar-brand" href="<?= app_url(); ?>"><?= theme_escape_html(app_sitename()); ?></a>
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
                           aria-label="<?= theme_escape_html(get_language_name($locale, false)); ?>">
                            <?= theme_escape_html(get_language_name($locale, true)); ?>
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
        <p>&copy; <?= date('Y'); ?> <?= t('footer.copyright'); ?>. <?= theme_escape_html(app_sitename()); ?></p>
    </div>
</footer>

<!-- Scripts - jQuery MUST load first (synchronous), rest uses defer.
     Add SRI integrity + crossorigin in production (see blog theme). -->
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

Each template follows the same pattern - no header/footer includes, just the content section.

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
cp -r public/themes/blog/assets/vendor/jquery.cookie public/themes/my-theme/assets/vendor/
cp -r public/themes/blog/assets/vendor/prism public/themes/my-theme/assets/vendor/
```

(If you don't need a library - e.g. no syntax highlighting - you can skip it, but then also remove its `<link>`/`<script>` from your header/footer.)

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

`ThemeRenderer` also defines a `FALLBACK_THEME` constant (`blog`) that it uses when the configured theme cannot be resolved or is missing required templates. This ensures the site never breaks due to a missing or misconfigured theme.

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
- [ ] **Search**: AJAX search returns results with `aria-live` dropdown, rate-limited warning appears after too many requests, pagination works on the `/search` page
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
diff <(cd public/themes/blog && find . -name "*.php" -o -name "*.json" | sort) \
     <(cd public/themes/my-theme && find . -name "*.php" -o -name "*.json" | sort)

# Compare helper function signatures (functions.php is a thin loader,
# so compare across all split modules)
for f in functions.php functions-i18n.php functions-nav.php functions-post.php \
         functions-media.php functions-comments.php; do
    grep -H "^function " "public/themes/blog/$f" 2>/dev/null | sort
done > /tmp/blog_fns.txt
for f in functions.php functions-i18n.php functions-nav.php functions-post.php \
         functions-media.php functions-comments.php; do
    grep -H "^function " "public/themes/my-theme/$f" 2>/dev/null | sort
done > /tmp/theme_fns.txt
diff /tmp/blog_fns.txt /tmp/theme_fns.txt
```

The blog theme keeps every helper behind a `function_exists()` guard, so a theme module can be copied over without triggering "function already declared" errors.

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
<!-- CORRECT - single source -->
<script src="assets/vendor/popper.js/umd/popper.min.js"></script>

<!-- WRONG - don't load from two places -->
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
| `functions.php` | `public/themes/[theme]/` | Thin loader - requires the modules below |
| `functions-i18n.php` | `public/themes/[theme]/` | `t()`, `locale_url()`, `language_switcher()`, locale helpers |
| `functions-nav.php` | `public/themes/[theme]/` | Menu tree + rendering, `convert_menu_link()` |
| `functions-post.php` | `public/themes/[theme]/` | Post/page retrieval, `prepare_post_card()`, URL builders |
| `functions-media.php` | `public/themes/[theme]/` | Slideshow, galleries, thumbnails |
| `functions-comments.php` | `public/themes/[theme]/` | `total_comment()`, `block_csrf()`, comment renderer |
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
| `download_file.php` | `public/themes/[theme]/` | Download handler (no header/footer wrapper) |
| `render-comments.php` | `public/themes/[theme]/` | Legacy comment renderer |
| `partials/card.php` | `public/themes/[theme]/partials/` | Shared post card markup |
| `partials/meta.php` | `public/themes/[theme]/partials/` | Author/date row for cards |
| `partials/paginator.php` | `public/themes/[theme]/partials/` | Shared pagination wrapper |
| `partials/comments.php` | `public/themes/[theme]/partials/` | Comment list + load-more |
| `index.php` | `public/themes/[theme]/` | Kept for compatibility (not used for routing) |

### Core System Integration Files

| File | Location | Purpose |
|------|----------|---------|
| `theme-caller.php` | `lib/utility/theme-caller.php` | `theme_identifier()`, `theme_dir()` - resolves active theme |
| `theme-navigation.php` | `lib/utility/theme-navigation.php` | `theme_navigation()` - menu data with locale filtering |
| `theme-escape.php` | `lib/utility/theme-escape.php` | `theme_escape_html()` - the single output-escaping boundary |
| `front-service.php` | `lib/utility/front-service.php` | `front_service()` - returns the shared `FrontService` instance |
| `front-paginator.php` | `lib/utility/front-paginator.php` | `front_paginator()` - returns a `Paginator` instance |
| `ThemeRenderer.php` | `lib/core/ThemeRenderer.php` | Renders header + content + footer; `render404()`; `blog` fallback |
| `Theme/ThemeHelper.php` | `lib/core/Theme/ThemeHelper.php` | Loads shared ViewModel classes; exposes `factory()` |
| `Theme/PostViewModel.php` | `lib/core/Theme/PostViewModel.php` | Escaped post data object rendered by templates |
| `Theme/PageViewModel.php` | `lib/core/Theme/PageViewModel.php` | Escaped page data object |
| `Theme/ArchiveViewModel.php` | `lib/core/Theme/ArchiveViewModel.php` | Escaped archive entry data object |
| `Theme/MenuViewModel.php` | `lib/core/Theme/MenuViewModel.php` | Escaped menu node (with children tree) |
| `Theme/SidebarViewModel.php` | `lib/core/Theme/SidebarViewModel.php` | Escaped sidebar aggregates (latest posts, categories, ...) |
| `Theme/ThemeViewModelFactory.php` | `lib/core/Theme/ThemeViewModelFactory.php` | Factory for the ViewModel objects |
| `Dispatcher.php` | `lib/core/Dispatcher.php` | Content validation, route → template dispatch, 404 handling |
| `HandleRequest.php` | `lib/core/HandleRequest.php` | Request handling, theme property setup |
| `ThemeDao.php` | `lib/dao/ThemeDao.php` | Theme CRUD with fallback |
| `ThemeService.php` | `lib/service/ThemeService.php` | Theme activation business logic |
| `ThemeController.php` | `lib/controller/ThemeController.php` | Theme admin page handling |
| `FrontService.php` | `lib/service/FrontService.php` | Frontend data retrieval (posts, pages, topics, tags, archives, search) |
| `ProtectedPostService.php` | `lib/service/ProtectedPostService.php` | Protected/public post resolution (decrypt, sanitize) |
| `FrontHelper.php` | `lib/core/FrontHelper.php` | ⚠ **Deprecated** - kept for backward compatibility. Use `FrontService` instead. |

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
| `assets/css/prism-override.css` | Prism code-block overrides |
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
| `assets/vendor/jquery.cookie/` | jQuery cookie plugin |
| `assets/vendor/prism/` | Prism.js syntax highlighting |

---

## Appendix: Quick Reference

### Template Tags Cheat Sheet

```php
// i18n
t('key')                              // Translate string (use %param% placeholders)
t('search.found_results', ['count' => 3, 'keyword' => 'php'])  // With params
locale_url('/path', 'es')             // Localized URL
get_locale()                          // Current locale
is_rtl()                              // RTL check

// Post retrieval & preparation
featured_post()                       // Random featured post
latest_posts(5)                       // Latest 5 posts
random_posts(1, 3)                    // 3 random posts
retrieve_detail_post($id)             // Single post by ID
posts_by_category($topicId)           // Posts in category (+ pagination)
posts_by_tag($tag)                    // Posts by tag
posts_by_archive(['month' => 3, 'year' => 2025])  // Posts in archive month
prepare_post_card($entry)             // → escaped PostViewModel (for partials/card.php)
prepare_sidebar()                     // → escaped SidebarViewModel
theme_post_url(['ID' => 5, 'post_slug' => 'slug'])  // Permalink-aware URL
theme_page_url(['ID' => 2, 'post_slug' => 'about'])  // Page URL

// Navigation
theme_navigation('public')            // Get raw menu items
front_navigation(0, $menu)            // Render menu HTML (builds MenuViewModel tree)
convert_menu_link($link, $enabled)    // Convert URL format
link_tag($postId)                     // Tag links for post
link_topic($postId)                   // Category links for post

// Comments
total_comment($postId)['total']       // Comment count (returns ['total' => int])
block_csrf()                          // CSRF token (hidden input)
render_comments_section($postId, 0)   // Comment section HTML

// Images
invoke_frontimg($filename)            // Featured image
invoke_responsive_image($file, 'medium')  // Responsive <picture>
invoke_hero_image($file, '', $alt)    // Hero image (fallback_url, alt)
invoke_gallery_image($file, $alt)     // Gallery thumbnail

// Security
theme_escape_html($string)            // THE output escaping boundary
safe_html($content)                   // For content pre-sanitized by paragraph_trim()
front_service()                       // Shared FrontService (replaces FrontHelper)

// Utility
make_date($timestamp)                 // Format date (display only, not for form inputs)
retrieve_site_url()                   // Site base URL
app_url()                             // App URL from config
theme_dir()                           // Current theme directory URL
nothing_found()                       // "No posts" message
```

### Recommended Development Workflow

```
1. Plan theme structure (copy blog theme as reference)
2. Create theme directory, theme.ini, and partials/ folder
3. Create functions.php (thin loader) + the five functions-*.php modules
4. Create header.php (HTML head, nav, CSS, scriptlog_vars)
5. Create footer.php (scripts, footer content, cookie consent)
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

> **Best Practice:** Always base your theme on `public/themes/blog/` - it contains all required functions (split into modules), correct template patterns (with shared `partials/`), the single `theme_escape_html()` escaping boundary, and working vendor configurations.

---

*End of Theme Developer Guide*
