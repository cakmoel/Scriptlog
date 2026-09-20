# HTMX Theme Developer Guide

**Project:** Blogware/Scriptlog CMS  
**Version:** 1.0.0 | **Last Updated:** July 2026

> **Audience:** Theme developers who want to build HTMX-powered themes using the Valdur theme as a reference. This guide covers the HTMX-specific patterns, backend helpers, fragment system, CSRF integration, and asset management for themes with zero jQuery dependency.

**Reference theme:** `public/themes/valdur/` - the Valdur HTMX theme ships with Blogware and implements every pattern described here.

---

## Table of Contents

1. [Overview & Architecture](#1-overview--architecture)
2. [Theme Directory Structure](#2-theme-directory-structure)
3. [Backend Helpers: request-helper.php](#3-backend-helpers-request-helperphp)
4. [Fragment System: partials/](#4-fragment-system-partials)
5. [CSRF Integration](#5-csrf-integration)
6. [htmx-enhanced.js Reference](#6-htmx-enhancedjs-reference)
7. [Dark Mode Implementation](#7-dark-mode-implementation)
8. [Gallery with Fancybox (Vanilla JS)](#8-gallery-with-fancybox-vanilla-js)
9. [Vendor Libraries & Asset Management](#9-vendor-libraries--asset-management)
10. [Creating an HTMX Theme - Step by Step](#10-creating-an-htmx-theme--step-by-step)
11. [HTMX vs Blog Theme: Key Differences](#11-htmx-vs-blog-theme-key-differences)
12. [Troubleshooting](#12-troubleshooting)
13. [Files Reference](#13-files-reference)

---

## 1. Overview & Architecture

### HTMX Is Optional

HTMX is never mandatory for themes. The blog theme (`public/themes/blog/`) remains 100% untouched. The Valdur theme demonstrates HTMX as an **alternative approach** - theme developers can choose which pattern to follow.

### Communication Flow: HTMX Theme ↔ Core

```
Theme Template (e.g., home.php)
    │
    ├── HTMX interactions (hx-get, hx-post, hx-trigger, hx-target)
    │   ├── Search:     hx-get="/search?q=" → SearchController → is_htmx_request()
    │   ├── Comments:   hx-post="/comment/{id}" → CommentsApiController
    │   ├── Unlock:     hx-post="/api/v1/posts/{id}/unlock" → ProtectedPostApiController
    │   ├── Pagination: hx-get="/blog?page=N" → hx-target="#post-list" (fragment swap)
    │   └── Locale:     POST /locale → LocaleController
    │
    ├── Calls functions.php helper functions
    │   ├── latest_posts()   → PostModel → DAO → DB
    │   ├── retrieve_page()  → PageModel  → DAO → DB
    │   └── front_navigation() → MenuDao  → DB
    │
    ├── Uses security/utility functions
    │   ├── htmlout()            → escape output
    │   ├── htmLawed()           → sanitize content
    │   ├── block_csrf()         → CSRF token (used in htmx-enhanced.js)
    │   └── invoke_frontimg()    → display images
    │
    └── Uses i18n functions
        ├── t()                  → translate strings
        ├── get_locale()         → current locale
        ├── locale_url()         → locale-prefixed URLs
        └── is_rtl()             → RTL detection
```

### Core Principles

1. **Same Resource, Different Representation** - HTMX requests use the same URL as full-page requests. The `HX-Request` header signals whether to return a fragment or a full page.
2. **Progressive Enhancement** - Every form has a working `action` + `method`. Every link has a working `href`. The theme renders fully without JavaScript.
3. **No jQuery** - All AJAX handled by HTMX. All DOM manipulation is vanilla JS. Fancybox 5+ is vanilla JS.
4. **Self-contained** - Each theme owns its `functions.php`, `partials/`, and `assets/`. No cross-theme coupling.

### HTMX Interactions Map

| # | Interaction | Trigger | Endpoint | Target | Swap |
|---|-------------|---------|----------|--------|------|
| 1 | Search (typeahead) | `keyup changed delay:300ms, search` | `GET /search?q=` | `#search-suggestions` | `innerHTML` |
| 2 | Protected post unlock | `submit` on form | `POST /api/v1/posts/{id}/unlock` | `#password-protected-{id}` | `outerHTML` |
| 3 | Comments load more | `click` on button | `GET /comment/{postId}?offset=` | `#comments` | `beforeend` |
| 4 | Comment submit | `submit` on form | `POST /comment/{postId}` | `#comments-section` | `innerHTML` |
| 5 | Pagination | `click` on page link | `GET /blog?page=` | `#post-list` | `innerHTML` |
| 6 | Archive filter | `click` on month link | `GET /archive/{month}/{year}` | `#post-list` | `innerHTML` |
| 7 | Language switcher | `click` on language | `POST /locale` | `body` | `outerHTML` |
| 8 | Cookie consent | `click` on accept | `POST /api/v1/gdpr/consent` | - | - |
| 9 | Dark mode toggle | `click` on toggle | Client-side only | `<html>` `data-theme` attr | - |

### Head Management

Because HTMX swaps only the target element (typically `body` for page navigation), the `<head>` content (title, canonical URL, meta description, Open Graph tags) never updates after navigation. The tab title stays on the previous page's value.

**Fix:** Use the `hx-head` attribute on `<body>` to tell HTMX which `<head>` elements to extract from the response:

```html
<body hx-head='{"title": "title"}'>
```

When HTMX receives the response HTML, it finds the `<title>` element and updates the document's `<title>` accordingly. For canonical URLs and meta tags, use `hx-head` with merge rules:

```html
<body hx-head='{"title": "title", "meta": "merge", "link": "merge"}'>
```

This enables canonical URL, meta description, and other `<head>` elements to update on every HTMX navigation.

**Without `hx-head`:** A fallback in `htmx-enhanced.js` reads from a `data-title` attribute on the swapped content:

```javascript
document.addEventListener('htmx:afterSwap', function(evt) {
    var titleEl = evt.detail.elt.querySelector('[data-title]');
    if (titleEl) document.title = titleEl.getAttribute('data-title');
});
```

Then in each template (e.g., `single.php`):
```html
<div data-title="<?= htmlspecialchars($pageTitle . ' — My Site') ?>">
```

**Effort:** Low (~30 min). The `hx-head` attribute on `<body>` is the simplest approach and covers title, canonical, and meta in one declaration.

### Two Fragment Rendering Paths

The backend has two distinct mechanisms for serving HTMX responses. Understanding both is essential for debugging and theme development.

**Path 1 — Page navigation** (`Dispatcher::renderHtmxFragment()` in `lib/core/Dispatcher.php`):
```
Request → Dispatcher → renderTheme('single') → is_htmx_request()?
  ├── Yes → require $themeDir/single.php  (template rendered, no header/footer)
  └── No →  header + single.php + footer
```
Used for: post-card clicks, breadcrumb links, tag navigation, previous/next post, pagination. The template receives data through `$GLOBALS` and function calls (e.g., `request_path()->param1`). Templates are rendered as full content templates — the same `.php` files listed in §2, just without header/footer wrapping.

**Path 2 — Functional interactions** (`render_htmx_fragment()` in `lib/utility/request-helper.php`):
```
Controller → render_htmx_fragment('search-results', $data)
  → require $themeDir/partials/search-results.php  (partial only)
```
Used for: search typeahead, comment submission, protected post unlock, cookie consent, locale switching. Data is passed explicitly via the `$data` array and extracted into the partial's scope.

**Key difference:** Path 1 renders a full template file (`single.php`, `blog.php`, etc.) with access to all global state. Path 2 renders a dedicated partial file (`partials/search-results.php`) with only the data explicitly passed by the controller. When debugging, check which path the interaction uses — this determines whether the issue is in the template, the partial, or the data passed to each.

---

## 2. Theme Directory Structure

### Required Files

```
public/themes/[theme-name]/
├── theme.ini              # Theme metadata (REQUIRED)
├── functions.php          # Theme functions & template tags (REQUIRED)
├── index.php              # Entry point (usually empty)
├── header.php             # HTML head, nav, CSS, HTMX init
├── footer.php             # Scripts, footer, dark mode fallback
├── home.php               # Homepage
├── single.php             # Single post view + HTMX unlock
├── page.php               # Static page
├── blog.php               # Blog listing + HTMX pagination
├── category.php           # Category archive
├── tag.php                # Tag archive
├── archive.php            # Monthly archive
├── archives.php           # Archive index
├── sidebar.php            # Sidebar + HTMX search
├── 404.php                    # 404 error page
├── comment.php                # Comment template (legacy, usually empty)
├── cookie-consent.php         # GDPR cookie consent
├── privacy.php                # Privacy policy page
├── render-comments.php        # Comments rendering function
├── search.php                 # Search results page
├── download.php               # Download page
├── download_file.php          # File download handler
├── partials/                  # HTMX fragment templates (20 files)
│   ├── archive-list.php       # Archive month listing
│   ├── breadcrumb.php         # Breadcrumb navigation
│   ├── category-list.php      # Category list
│   ├── comment-form.php       # Comment form fragment
│   ├── comment-item.php       # Single comment
│   ├── comment-list.php       # Comment list for load-more
│   ├── comment-success.php    # Comment submission success
│   ├── cookie-banner.php      # Cookie consent banner
│   ├── gallery-item.php       # Single gallery image
│   ├── locale-menu.php        # Language switcher menu
│   ├── navbar.php             # Navigation bar fragment
│   ├── pagination.php         # Pagination links (configurable hx-target)
│   ├── post-card.php          # Single post card (hx-get click navigation)
│   ├── post-list.php          # Paginated post listing wrapper
│   ├── search-form.php        # Search form with HTMX typeahead
│   ├── search-results.php     # Search result items
│   ├── tag-list.php           # Tag list
│   ├── toast.php              # Toast notification
│   ├── unlock-error.php       # Wrong password error + retry
│   └── unlock-success.php     # Decrypted post content
├── lang/                      # Translation files
│   └── en.json                # English (always required)
└── assets/                    # Theme assets
    ├── css/
    │   ├── valdur-theme.css   # All styles: design tokens, components, comments, cookies, toasts
    │   └── valdur-theme.min.css # Minified production version
    ├── js/
    │   ├── htmx-enhanced.js   # HTMX config + CSRF + a11y + Lucide + Fancybox + dark mode
    │   └── htmx-enhanced.min.js
    └── img/
        ├── favicon.svg
        ├── hero.svg
        ├── logo.svg
        └── placeholder.svg
```

### Partial Naming Convention

Partials use `$args['key']` for data passing (never `extract()`; see note below):

> **Note on `extract()`:** The backend helper `render_htmx_fragment()` at `lib/utility/request-helper.php:64` uses `extract($data)` internally before including the partial. This is a convenience for the 3 backend callers (search, comments, unlock controllers). Theme partials should **always** use the `$args['key']` pattern shown below. The `extract()` call lives in the backend utility, not in the theme layer — all 20 Valdur partials correctly use `$args['key']`. If you pass `$data` to `render_htmx_fragment()`, those variables will exist as loose globals in the partial scope. For consistency, still access them via `$args['key']` within the partial file.

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
$post = $args['post'] ?? [];
$showExcerpt = $args['excerpt'] ?? true;
if (empty($post)) return;
// ... render HTML
?>
```

---

## 3. Backend Helpers: request-helper.php

The file `lib/utility/request-helper.php` provides functions to detect HTMX requests and render fragments:

```php
function is_htmx_request(): bool
{
    return isset($_SERVER['HTTP_HX_REQUEST'])
        && $_SERVER['HTTP_HX_REQUEST'] === 'true';
}

function htmx_target(): ?string
{
    return $_SERVER['HTTP_HX_TARGET'] ?? null;
}

function htmx_trigger(): ?string
{
    return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
}

function render_htmx_fragment(string $fragment, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=utf-8');

    $activeTheme = function_exists('theme_identifier') ? theme_identifier() : null;
    $themeDir = '';

    if (is_array($activeTheme) && isset($activeTheme['theme_directory'])) {
        $themeDir = APP_ROOT . APP_THEME . DIRECTORY_SEPARATOR
                    . $activeTheme['theme_directory'] . DIRECTORY_SEPARATOR;
    } else {
        $themeDir = APP_ROOT . APP_THEME . DIRECTORY_SEPARATOR . 'valdur' . DIRECTORY_SEPARATOR;
    }

    $partialPath = $themeDir . 'partials' . DIRECTORY_SEPARATOR . $fragment . '.php';

    if (!file_exists($partialPath)) {
        http_response_code(500);
        echo '<!-- Fragment not found: ' . htmlspecialchars($fragment, ENT_QUOTES, 'UTF-8') . ' -->';
        return;
    }

    if (!empty($data)) {
        extract($data);
    }

    require $partialPath;
}
```

### When to Use `is_htmx_request()`

This function is used in controllers that serve both full-page and fragment responses:

| Controller / Handler | Method | Fragment | HTMX Trigger |
|----------------------|--------|----------|--------------|
| `SearchController` | `search()` | `partials/search-results.php` | Search input typeahead |
| `ProtectedPostApiController` | `unlock()` | `partials/unlock-success.php` | Unlock form |
| `CommentsApiController` | `store()`, `index()` | `partials/comment-success.php`, `comment-list.php` | Comment submit / load more |
| `LocaleController` | `switch()` | `partials/navbar.php` | Language switcher |

### Rendering Fragments in Templates

The `render_partial()` function (defined in each theme's `functions.php`) is called from template files to include partials:

```php
<?php render_partial('post-card', ['post' => $post]); ?>
```

The shared backend `render_htmx_fragment()` in `request-helper.php` works similarly but resolves against the active theme. Functions.php wrappers like `render_partial()` provide a shorter, theme-specific alias.

---

## 4. Fragment System: partials/

Partials are reusable HTML fragments that can be rendered both as part of a full page and as HTMX swap targets. They live in the active theme's `partials/` directory.

### post-card.php

The `post-card.php` partial renders a single post card with HTMX navigation. It uses the `$args['key']` pattern for data passing and falls back to query string URLs when permalinks are disabled.

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
$post = $args['post'] ?? [];
$permalinkEnabled = function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes';
$showExcerpt = $args['excerpt'] ?? true;
if (empty($post)) return;

$postId = (int)($post['ID'] ?? 0);
$postTitle = htmlspecialchars($post['post_title'] ?? '', ENT_QUOTES, 'UTF-8');
$postSlug = $post['post_slug'] ?? '';
$postDate = $post['created_at'] ?? $post['modified_at'] ?? $post['post_date'] ?? '';
$postSummary = htmlspecialchars($post['post_summary'] ?? '', ENT_QUOTES, 'UTF-8');
$postContent = $post['post_content'] ?? '';
$mediaFile = $post['media_filename'] ?? '';
$postTags = $post['post_tags'] ?? '';

$excerpt = $postSummary ?: htmlspecialchars(strip_tags(mb_substr($postContent, 0, 300)), ENT_QUOTES, 'UTF-8');

$url = $permalinkEnabled
    ? '/post/' . $postId . '/' . rawurlencode($postSlug)
    : '?p=' . $postId;
?>
<article class="card post-card"
         hx-get="<?= htmlout($url) ?>"
         hx-target="body"
         hx-push-url="true"
         hx-trigger="click"
         hx-indicator="#htmx-global-indicator">
    <?php if ($mediaFile): ?>
    <div class="card-img-wrapper">
        <?= invoke_responsive_image($mediaFile, 'medium', true, $postTitle, 'card-img', false, 'lazy') ?>
    </div>
    <?php endif; ?>
    <div class="card-body">
        <header class="card-header">
            <time class="card-date" datetime="<?= htmlspecialchars($postDate, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(date('F j, Y', strtotime($postDate)), ENT_QUOTES, 'UTF-8') ?>
            </time>
            <h2 class="card-title"><?= $postTitle ?></h2>
        </header>
        <?php if ($showExcerpt && $excerpt): ?>
        <p class="card-excerpt"><?= htmlspecialchars(mb_substr($excerpt, 0, 200), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <footer class="card-footer">
            <span class="card-link"><?= t('post.read_more') ?></span>
            <?php if ($postTags): ?>
            <div class="card-tags">
                <?php foreach (array_slice(explode(',', $postTags), 0, 3) as $tag): ?>
                <span class="tag tag-sm"><?= htmlspecialchars(trim($tag), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </footer>
    </div>
</article>
```

### pagination.php

Pagination partial supports HTMX with configurable target. Uses a `render_page_link()` helper for consistent link rendering:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

$current = $args['current'] ?? 1;
$total = $args['total'] ?? 1;
$baseUrl = $args['base'] ?? '?page=';
$hxTarget = $args['hxTarget'] ?? '#post-list';  // Default targets #post-list
$hxPush = $args['hxPush'] ?? 'true';

if ($total <= 1) return;

if (!function_exists('render_page_link')) {
function render_page_link(string $url, string $label, int $page, bool $active, bool $disabled, array $attrs = []): string
{
    $cls = 'pagination-link';
    if ($active) $cls .= ' pagination-link--active';
    if ($disabled) $cls .= ' pagination-link--disabled';

    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . htmlout($k) . '="' . htmlout($v) . '"';
    }

    if ($disabled) {
        return '<span class="' . $cls . '" aria-disabled="true"' . $extra . '>' . $label . '</span>';
    }

    return '<a href="' . htmlout($url . $page) . '" class="' . $cls . '" data-page="' . $page . '"' . $extra . '>' . $label . '</a>';
}
}
?>
<nav class="pagination" aria-label="<?= t('pagination.page') ?>">
    <?= render_page_link($baseUrl, t('pagination.previous'), $current - 1, false, $current <= 1, [
        'rel' => 'prev',
        'hx-get' => $baseUrl . ($current - 1),
        'hx-target' => $hxTarget,
        'hx-push-url' => $hxPush,
        'hx-indicator' => '#htmx-global-indicator',
    ]) ?>

    <?php for ($i = 1; $i <= $total; $i++): ?>
        <?php if ($i === $current): ?>
            <?= render_page_link($baseUrl, (string)$i, $i, true, false) ?>
        <?php elseif ($i === 1 || $i === $total || abs($i - $current) <= 2): ?>
            <?= render_page_link($baseUrl, (string)$i, $i, false, false, [
                'hx-get' => $baseUrl . $i,
                'hx-target' => $hxTarget,
                'hx-push-url' => $hxPush,
                'hx-indicator' => '#htmx-global-indicator',
            ]) ?>
        <?php elseif (abs($i - $current) === 3): ?>
            <span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>
        <?php endif; ?>
    <?php endfor; ?>

    <?= render_page_link($baseUrl, t('pagination.next'), $current + 1, false, $current >= $total, [
        'rel' => 'next',
        'hx-get' => $baseUrl . ($current + 1),
        'hx-target' => $hxTarget,
        'hx-push-url' => $hxPush,
        'hx-indicator' => '#htmx-global-indicator',
    ]) ?>
</nav>
```

---

## 5. CSRF Integration

### Flow

1. **Page load**: CSRF token is rendered in a `<script>` block in `header.php`:
   ```php
   <script>
   window.scriptlog_vars = {
       csrf_token: '<?= htmlout(csrf_generate_token('csrfToken')) ?>',
       theme_url: '<?= htmlout($themeUrl) ?>',
       site_url: '<?= htmlout($siteUrl) ?>'
   };
   </script>
   ```

2. **HTMX request**: `htmx-enhanced.js` reads the token from `window.scriptlog_vars.csrf_token` and sends it as the `HX-CSRF-Token` header.

3. **Server validation**: Controllers validate via `csrf_check_token()`.

### htmx-enhanced.js CSRF Handler

```javascript
document.addEventListener('htmx:configRequest', function(evt) {
    var token = '';
    if (window.scriptlog_vars && window.scriptlog_vars.csrf_token) {
        token = window.scriptlog_vars.csrf_token;
    }
    evt.detail.headers['HX-CSRF-Token'] = token;
});
```

### What Stays the Same

- `csrf_check_token()` - no changes needed
- `block_csrf()` - still used for traditional form submissions
- Existing form CSRF tokens - unchanged

---

## 6. htmx-enhanced.js Reference

This is the core JavaScript file for HTMX themes. It handles configuration, CSRF, Lucide/Fancybox reinitialization, dark mode, and accessibility announcements.

### Configuration

```javascript
htmx.config.defaultSwapStyle = 'innerHTML';
htmx.config.defaultSettleDelay = 20;
htmx.config.historyEnabled = false;   // Prevent back-button issues
```

### Event Handlers

| Event | Handler | Purpose |
|-------|---------|---------|
| `htmx:configRequest` | Adds `HX-CSRF-Token` header | CSRF for all HTMX requests |
| `htmx:afterSwap` | `initLucide()`, `initFancybox()` | Reinitialize icons and lightbox after DOM swap |
| `htmx:afterSettle` | `reapplyTheme()` | Re-apply dark mode after HTMX settles |
| `htmx:afterRequest` | `announceToScreenReader()` | A11y - announce dynamic content changes |
| `htmx:responseError` | `announceToScreenReader()` | A11y - announce errors |
| `DOMContentLoaded` | `reapplyTheme()`, `initFancybox()`, event listeners | Initial setup |

### CSS Transitions for Content Swaps (Optional)

Adding CSS transitions between page states improves the perception of smoothness. Without transitions, content appears/disappears instantly when HTMX swaps the DOM.

**Basic opacity transition:**

```css
#main-content {
    transition: opacity 150ms ease-in;
}

.htmx-request #main-content {
    opacity: 0.6;
}
```

The `.htmx-request` class is applied to the target element automatically during the request. This creates a subtle fade effect that signals activity without needing the full `htmx-indicator`.

**CSS View Transitions API (modern browsers):**

For browsers that support it (Chrome 111+, Firefox 121+), the View Transitions API provides smooth crossfade animations:

```css
@keyframes fade-in {
    from { opacity: 0; }
}
@keyframes fade-out {
    to { opacity: 0; }
}

::view-transition-old(root) {
    animation: 150ms ease-out fade-out;
}
::view-transition-new(root) {
    animation: 150ms ease-in fade-in;
}
```

HTMX 2.x supports the View Transitions API natively. No JavaScript changes needed — just the CSS above and the browser handles the rest.

**Best practice:** Use opacity transitions (150-200ms). Avoid transform or layout animations that trigger repaints on every frame.

### Full Structure

```javascript
(function() {
  'use strict';

  // 1. HTMX configuration
  htmx.config.defaultSwapStyle = 'innerHTML';
  htmx.config.defaultSettleDelay = 20;
  htmx.config.historyEnabled = false;

  // 2. CSRF token injection
  document.addEventListener('htmx:configRequest', function(evt) { ... });

  // 3. Lucide reinitialization
  function initLucide() { ... }

  // 4. Fancybox reinitialization
  function initFancybox() { ... }

  // 5. Theme re-application
  function reapplyTheme() {
    var theme = localStorage.getItem('valdur-theme');
    // Check stored preference, then OS preference, then default light
  }

  // 6. Screen reader announcements
  function announceToScreenReader(message) { ... }

  // 7. Event listeners
  document.addEventListener('htmx:afterSwap', ...);
  document.addEventListener('htmx:afterSettle', ...);
  document.addEventListener('htmx:afterRequest', ...);
  document.addEventListener('htmx:responseError', ...);

  // 8. DOMContentLoaded - initial setup
  document.addEventListener('DOMContentLoaded', function() { ... });

  // 9. prefers-color-scheme change listener
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', ...);
})();
```

---

## 7. Dark Mode Implementation

### Flash Prevention

A synchronous `<script>` in `<head>` runs **before CSS paints**, preventing the Flash of Wrong Theme (FOWT):

```html
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <script>
    (function() {
        var theme = localStorage.getItem('valdur-theme');
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
    </script>
</head>
```

### CSS Variable Design Tokens

```css
/* Light (default) */
:root, [data-theme="light"] {
    --color-bg: #F4F7F6;
    --color-surface: #FFFFFF;
    --color-text: #191E24;
    --color-primary: #0077C0;
    /* ... 30+ design tokens */
    color-scheme: light;
}

/* Dark */
[data-theme="dark"] {
    --color-bg: #11161D;
    --color-surface: #1B222B;
    --color-text: #F4F7F6;
    --color-primary: #4DB8FF;
    /* ... 30+ design tokens */
    color-scheme: dark;
}
```

### Toggle Button (Vanilla JS, No Event Listener Dependency)

```html
<button class="btn btn-icon theme-toggle"
        aria-label="Toggle dark mode"
        onclick="var t=document.documentElement.getAttribute('data-theme');
                 t=t==='dark'?'light':'dark';
                 document.documentElement.setAttribute('data-theme',t);
                 localStorage.setItem('valdur-theme',t);
                 this.querySelector('i').setAttribute('data-lucide',t==='dark'?'sun':'moon');
                 lucide.createIcons()">
    <i data-lucide="moon" aria-hidden="true" width="20" height="20"></i>
</button>
```

### OS Preference Detection

The synchronous `<script>` in `<head>` checks localStorage only (fastest path). The CSS `@media (prefers-color-scheme: dark)` rule handles OS-level dark mode at the stylesheet level. The `htmx-enhanced.js` function `reapplyTheme()` adds OS detection for post-HTMX-swap scenarios:

```javascript
function reapplyTheme() {
    var theme = localStorage.getItem('valdur-theme');
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else if (theme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('valdur-theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
    }
}
```

### HTMX Compatibility - Re-apply After Swaps

```javascript
document.addEventListener('htmx:afterSettle', function(evt) {
    reapplyTheme();
});
```

### prefers-color-scheme Change Listener

```javascript
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    var stored = localStorage.getItem('valdur-theme');
    if (!stored) {
        document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
    }
});
```

---

## 8. Gallery with Fancybox (Vanilla JS)

Fancybox 5+ no longer requires jQuery. Use the CDN version:

```html
<!-- header.php -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
```

### Gallery Partial (gallery-item.php)

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
$image = $args['image'] ?? [];
$galleryId = $args['galleryId'] ?? 'gallery-main';
if (empty($image)) return;

$mediaFile = $image['media_filename'] ?? '';
$caption = $image['media_caption'] ?? '';
$altText = $caption ?: basename($mediaFile);
?>
<div class="gallery-item" data-gallery-id="<?= htmlout($galleryId) ?>">
    <a href="<?= htmlout(app_url() . '/public/files/pictures/' . rawurlencode($mediaFile)) ?>"
       class="gallery-link"
       data-fancybox="<?= htmlout($galleryId) ?>"
       data-caption="<?= htmlout($caption) ?>"
       aria-label="<?= htmlout($altText) ?>">
        <?= invoke_responsive_image($mediaFile, 'medium', true, $altText, 'gallery-img', false, 'lazy') ?>
    </a>
    <?php if ($caption): ?>
    <div class="gallery-caption">
        <p><?= htmlout($caption) ?></p>
    </div>
    <?php endif; ?>
</div>
```

### Fancybox Re-init After HTMX Swap

```javascript
function initFancybox() {
    if (typeof Fancybox !== 'undefined' && document.querySelector('[data-fancybox]')) {
        Fancybox.bind('[data-fancybox]', {
            Thumbs: false,
            Toolbar: { display: ['close'] }
        });
    }
}

document.addEventListener('htmx:afterSwap', function(evt) {
    initFancybox();
});
```

---

## 9. Vendor Libraries & Asset Management

### CDN Dependencies

| Library | CDN URL | Size | Purpose |
|---------|---------|------|---------|
| HTMX 2.x | `https://unpkg.com/htmx.org@2.0.4` | 14KB | AJAX interactions |
| HTMX Extensions | `https://unpkg.com/htmx.org/dist/ext/response-targets.js` | 2KB | Error handling |
| HTMX Extensions | `https://unpkg.com/htmx.org/dist/ext/loading-states.js` | 1KB | Loading indicators |
| HTMX Extensions | `https://unpkg.com/htmx.org/dist/ext/preload.js` | 1KB | Link preloading |
| Lucide | `https://unpkg.com/lucide@latest` | 5KB | Icons (tree-shaken) |
| Fancybox 5 | CDN via jsdelivr (see §8) | 12KB | Gallery lightbox |
| Google Fonts | `fonts.googleapis.com` | - | Rubik + Geist Mono |

**Total JS**: ~22KB gzipped (~23KB with preload extension) (vs ~177KB for the Bootstrap + jQuery blog theme)

### Link Preloading (Optional)

The HTMX `preload` extension prefetches links on `mouseenter` (desktop) or `touchstart` (mobile), eliminating the network round-trip delay on click. This reduces perceived navigation latency to near-zero.

**Setup:**

1. Add the CDN script in `header.php`:
   ```html
   <script src="https://unpkg.com/htmx.org/dist/ext/preload.js"></script>
   ```
2. Add `hx-ext="preload"` to navigation containers:
   ```html
   <nav hx-ext="preload" class="pagination" aria-label="Pagination">
   ```
   Or use `preload="mouseover"` for more aggressive prefetching:
   ```html
   <div class="post-list" hx-ext="preload" preload="mouseover">
   ```

**Preload modes:**

| Mode | Trigger | Behavior |
|------|---------|----------|
| `preload="mouseover"` (default) | Mouse hover (desktop), touch start (mobile) | Prefetches on hover intent |
| `preload="mousedown"` | Mouse button press | Earliest possible without false positives |

**Considerations:**
- Adds ~1KB to JS payload
- Only preloads links with `hx-get` attributes (not plain `<a>` tags)
- Does not preload `hx-post`, `hx-put`, or `hx-delete`
- Respects `hx-target` — preloaded response targets the same element as the click would

### Files NOT Included (HTMX Replaces)

| Blog Theme File | HTMX Replacement |
|-----------------|-------------------|
| `search.js` | `hx-get` on search input |
| `unlock-post.js` | `hx-post` on unlock form |
| `comment-submission.js` | `hx-post` on comment form |
| `load-comment.js` | `hx-get` on load-more button |
| `jquery.min.js` | Not needed (vanilla JS) |
| `bootstrap.min.js` | Not needed (custom CSS) |
| `popper.min.js` | Not needed |

### Minification

```bash
php tmp/minify.php
```

This processes all themes, including your HTMX theme. Source files use `.css`/`.js` extensions; production loads `.min.css`/`.min.js`.

---

## 10. Creating an HTMX Theme - Step by Step

### Step 1: Create Theme Directory

```bash
mkdir -p public/themes/my-htmx-theme/{partials,lang,assets/{css,js,img}}
```

### Step 2: Create theme.ini

```ini
[info]
theme_name = "My HTMX Theme"
theme_designer = "Your Name"
theme_description = "A beautiful HTMX-powered theme"
theme_directory = "my-htmx-theme"

[capabilities]
theme_features[] = htmx
theme_features[] = lazy-loading
theme_features[] = i18n
theme_features[] = dark-mode
```

### Step 3: Create functions.php

Copy from `public/themes/valdur/functions.php`. This file provides all required helpers wrapped in `!function_exists()` guards:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// i18n
if (!function_exists('t')) {
function t(string $key, array $params = []): string { /* ... */ }
}

// CSRF
if (!function_exists('block_csrf')) {
function block_csrf(): string { /* ... */ }
}

// Navigation
if (!function_exists('front_navigation')) {
function front_navigation(int $parent, array $menu): string { /* ... */ }
}

// Post retrieval (call model layer)
if (!function_exists('latest_posts')) {
function latest_posts(int $limit = 5, int $position = 0): array { /* ... */ }
}

// ... see valdur/functions.php for complete reference
```

### Step 4: Create header.php

Key elements:

```html
<!DOCTYPE html>
<html lang="<?= get_locale() ?>" dir="<?= get_html_dir() ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Dark mode flash prevention - MUST be first script -->
    <script>
    (function() {
        var theme = localStorage.getItem('my-theme');
        if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
    </script>

    <!-- CSS: theme stylesheet -->
    <link rel="stylesheet" href="<?= theme_dir() ?>assets/css/theme.css">

    <!-- CDN: HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script src="https://unpkg.com/htmx.org/dist/ext/response-targets.js"></script>
    <script src="https://unpkg.com/htmx.org/dist/ext/loading-states.js"></script>

    <!-- CDN: Lucide icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- CSRF token for HTMX -->
    <script>
    window.scriptlog_vars = {
        csrf_token: '<?= htmlout(csrf_generate_token('csrfToken')) ?>',
        theme_url: '<?= htmlout(theme_dir()) ?>',
        site_url: '<?= htmlout(retrieve_site_url()) ?>'
    };
    </script>
</head>
<body hx-ext="response-targets, loading-states">
    <div id="a11y-announcer" class="sr-only" role="status" aria-live="polite"></div>
    ...
```

### Step 5: Create footer.php

```html
</main>
<footer>...</footer>

<!-- htmx-enhanced.js - custom script -->
<script src="<?= theme_dir() ?>assets/js/htmx-enhanced.js"></script>
</body>
</html>
```

### Step 6: Create htmx-enhanced.js

Copy from `public/themes/valdur/assets/js/htmx-enhanced.js` and customize:

```javascript
(function() {
    'use strict';

    htmx.config.defaultSwapStyle = 'innerHTML';
    htmx.config.defaultSettleDelay = 20;
    htmx.config.historyEnabled = false;

    // CSRF
    document.addEventListener('htmx:configRequest', function(evt) {
        var token = window.scriptlog_vars?.csrf_token || '';
        evt.detail.headers['HX-CSRF-Token'] = token;
    });

    // Reinitialize Lucide after HTMX swaps
    document.addEventListener('htmx:afterSwap', function(evt) {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    // Re-apply dark mode after HTMX settles
    document.addEventListener('htmx:afterSettle', function(evt) {
        var theme = localStorage.getItem('my-theme');
        if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    });

    // A11y announcements
    document.addEventListener('htmx:responseError', function(evt) {
        var announcer = document.getElementById('a11y-announcer');
        if (announcer) announcer.textContent = 'Error loading content';
    });
})();
```

### Step 7: Create Partials

Create fragments for HTMX swap targets. Each partial follows the `$args['key']` pattern.

### Step 8: Create Template Files

Each template follows the same pattern - no `call_theme_header()` or `call_theme_footer()` includes:

```php
<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
// Template content only
?>
<div class="container">
    <?php foreach ($posts as $post): ?>
        <?php render_partial('post-card', ['post' => $post]); ?>
    <?php endforeach; ?>
    <?php render_partial('pagination', $paginationData); ?>
</div>
```

### Step 9: Add Translation Files

Create `lang/en.json` with all translation keys used in your theme.

### Step 10: Register and Test

1. Go to **Admin panel → Settings → Themes**
2. Activate your theme
3. Test all HTMX interactions (search, pagination, comments, unlock, locale, dark mode)
4. Test non-JS fallback (disable JavaScript, verify forms/links still work)

---

## 11. HTMX vs Blog Theme: Key Differences

| Aspect | Blog Theme (Bootstrap) | HTMX Theme (Valdur) |
|--------|-----------------------|---------------------|
| **CSS Framework** | Bootstrap 4 | Custom CSS with design tokens |
| **JavaScript** | jQuery 3.x + Bootstrap JS + 8 plugin files | HTMX 2.x + htmx-enhanced.js + Lucide + Fancybox |
| **Total JS Payload** | ~177KB | ~22KB |
| **AJAX** | jQuery.ajax() | HTMX (`hx-get`, `hx-post`, etc.) |
| **Icons** | Font Awesome 4 | Lucide (SVG icons) |
| **Lightbox** | Fancybox 3 (jQuery plugin) | Fancybox 5 (vanilla JS) |
| **Dark Mode** | None | CSS custom properties + localStorage |
| **Partials** | None - full page templates only | `partials/` directory with 20+ fragments |
| **Backend Pattern** | Template renders full page every request | `is_htmx_request()` returns fragments for HTMX |
| **CSRF via Headers** | Hidden input fields | `HX-CSRF-Token` header |
| **form-security.php** | `csrf_check_token($_POST['csrfToken'])` | `csrf_check_token($_SERVER['HTTP_HX_CSRF_TOKEN'])` |
| **Loading Indicators** | Spinner DIVs | `htmx-indicator` CSS class + `loading-states` extension |
| **Accessibility** | Basic ARIA | `aria-live` announcer, `htmx:afterRequest` announcements |
| **Dependencies** | Vendor folder (Bootstrap, jQuery, etc.) | CDN only (no vendor folder needed) |
| **Minification** | `tmp/minify.php` | Same - processes all themes |

---

## 12. Troubleshooting

### HTMX Not Loading

- Check CDN URLs in `header.php`
- Verify no ad-blocker is blocking `unpkg.com`
- Check browser console for CSP errors
- The theme still renders fully without HTMX (progressive enhancement)

### CSRF Token Expired

- Token is generated per-page-load
- For long editing sessions, refresh the page
- Check that `window.scriptlog_vars.csrf_token` is set (browser console)

### Lucide Icons Not Showing After HTMX Swap

- `htmx:afterSwap` handler calls `lucide.createIcons()`
- Check that htmx-enhanced.js loads before any HTMX content swaps
- Verify Lucide CDN is loaded

### Fancybox Not Opening After HTMX Swap

- `initFancybox()` binds to `[data-fancybox]` elements in the DOM
- After HTMX swaps, `htmx:afterSwap` calls `initFancybox()` again
- Check that `Fancybox.bind()` runs with the correct selector

### Dark Mode Flash

- The synchronous `<script>` in `<head>` must execute before CSS paints
- Check that the script is the first `<script>` in `<head>`
- Verify `localStorage` key name matches (`valdur-theme` or your custom key)

### Double Content / Duplicate AJAX

- Links with `hx-get` should also have a valid `href` (for non-JS fallback)
- Forms with `hx-post` should also have `action` + `method`
- HTMX does not interfere with jQuery - but don't use jQuery in HTMX themes

### Pagination Returning Full Page Instead of Fragment

- Pagination links default to `hx-target="#post-list"` for fragment swaps
- Ensure the post listing wrapper has `id="post-list"` in all archive templates (blog.php, category.php, tag.php, archive.php)
- If pagination renders a full page, check that the backend passes pagination data as a structured array suitable for `render_partial('pagination', ...)` rather than as a pre-rendered HTML string
- The `$hxTarget` param is configurable per-call: `render_partial('pagination', ['hxTarget' => '#custom-target'])`

---

## 13. Files Reference

### Core Backend Files

| File | Purpose |
|------|---------|
| `lib/utility/request-helper.php` | `is_htmx_request()`, `htmx_target()`, `htmx_trigger()`, `render_htmx_fragment()` |
| `lib/controller/SearchController.php` | HTMX-aware search (`is_htmx_request()` for fragments) |
| `lib/controller/LocaleController.php` | HTMX-aware locale switching |
| `lib/controller/api/ProtectedPostApiController.php` | HTMX-aware password unlock |

### Valdur Theme Reference Files

| File | Purpose |
|------|---------|
| `public/themes/valdur/theme.ini` | Theme metadata + capability flags |
| `public/themes/valdur/functions.php` | Complete theme functions (813 lines) |
| `public/themes/valdur/header.php` | HTMX CDN, CSRF token, Fancybox CDN, dark mode flash prevention |
| `public/themes/valdur/footer.php` | htmx-enhanced.js loading |
| `public/themes/valdur/privacy.php` | Privacy policy page (database-driven with i18n fallback) |
| `public/themes/valdur/assets/js/htmx-enhanced.js` | HTMX config, CSRF, Lucide, Fancybox, a11y, dark mode |
| `public/themes/valdur/assets/css/valdur-theme.css` | All-in-one stylesheet: design tokens, dark mode, comments, cookies, toasts (2294 lines) |
| `public/themes/valdur/lang/en.json` | Translation dictionary |
| `public/themes/valdur/partials/` | 20 fragment templates |
| `plan/HTMX_PROGRESSIVE_ENHANCEMENT_REVISED.md` | Implementation plan and design decisions |

---

*End of HTMX Theme Developer Guide*
