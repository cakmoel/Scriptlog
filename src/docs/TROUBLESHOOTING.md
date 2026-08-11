# Troubleshooting Guide

> **Purpose**: Common problems and how to fix them.
> **Audience**: Anyone working on this project, from beginners to experienced developers.
> **When to update**: Add an entry whenever you find and fix a bug.

---

## Table of Contents

- [Installation Issues](#installation-issues)
- [Database Issues](#database-issues)
- [Post/Content Issues](#postcontent-issues)
- [Image/Media Issues](#imagemedia-issues)
- [Authentication/Session Issues](#authenticationsession-issues)
- [i18n/Translation Issues](#i18ntranslation-issues)
- [API Issues](#api-issues)
- [Navigation/URL Issues](#navigationurl-issues)
- [Search Issues](#search-issues)
- [Theme Issues](#theme-issues)
- [Server/Config Issues](#serverconfig-issues)

---

## Installation Issues

### 1. MySQL Socket Connection Error

**Problem**: Installation fails with `Connection refused` or `No such file or directory` when using `localhost`.

**Root Cause**: PHP tries to connect through a socket file when you use `localhost`, but your MySQL server expects a network connection (TCP).

**Solution**: Use `127.0.0.1` instead of `localhost` in the database host field during installation.

### 2. Installer Timeout - Batched Translation Inserts

**Problem**: `install/setup-db.php` fails with "Maximum execution time of 60 seconds exceeded" on Windows 10 with IIS.

**Root Cause**: The installer ran about 980 separate `INSERT` statements one by one inside a transaction, which is very slow on IIS/FastCGI setups.

**Solution**: Changed to batch inserts. Instead of one row at a time, it now inserts 100 rows per query, cutting round trips from 980 to about 10.

**Files**: `install/include/setup.php` (lines 807-836)

### 3. IIS web.config Duplicate MIME Type

**Problem**: After installation on Windows 10 with IIS, `install/finish.php` returns `HTTP Error 500.19 - Cannot add duplicate collection entry of type 'mimeMap'`.

**Root Cause**: IIS 10+ already defines `.webp` and `.woff2` MIME types at the server level. The installer tried to add them again without removing the existing entries first.

**Solution**: Added `<remove fileExtension=".webp" />` and `<remove fileExtension=".woff2" />` before their respective `<add>` directives.

**Files**: `install/include/setup.php` - `generate_server_config()` function

### 4. Installation Double Slash Redirect

**Problem**: Redirect URLs contain double slashes: `https://blogware.site//install`.

**Root Cause**: When `$_SERVER['PHP_SELF']` is `/install/`, running `dirname('/install/')` returns `/`, which combines with another `/` to make `//`.

**Solution**: Normalize the path with `rtrim()` and check if `dirname()` returns just `/` (set it to empty string in that case).

**Files**: `install/include/setup.php` - `current_url()` function

---

## Database Issues

### 5. Missing `post_keyword` Column

**Problem**: PHP error when viewing a post - `PostModel` tries to use a `post_keyword` column that doesn't exist in the database.

**Root Cause**: The column definition was missing from `install/include/dbtable.php`.

**Solution**: Added `post_keyword VARCHAR(255) DEFAULT NULL AFTER post_tags` to the `tbl_posts` table definition.

### 6. Incomplete `knownTables` Array

**Problem**: Table prefix handling fails because the `Db` class doesn't recognize certain tables.

**Root Cause**: The `knownTables` array in `lib/core/Db.php` was missing several tables used by the application.

**Solution**: Added all 21 tables to the `knownTables` array.

### 7. Table Prefix Not Applied in Utility Functions

**Problem**: Some utility functions create new database connections and don't use the correct table prefix.

**Root Cause**: `medooin.php` creates a new Medoo connection instead of reusing the one from `Registry`.

**Solution**: Modified `medooin.php` to use `Registry::get('dbc')` instead of creating a new connection. Also updated `db-mysqli.php` for both PDO and mysqli compatibility.

---

## Post/Content Issues

### 8. Protected Post Update Fails - Empty Password Fields

**Problem**: Editing a password-protected post without changing the password silently discards your tag, category, and content changes.

**Root Cause**: `PostDao::updatePost()` always includes `post_password` and `passphrase` in the SQL UPDATE. When these are empty strings (because you didn't change the password), the whole transaction fails.

**Solution**:
- `PostDao.php`: Only include `post_password` and `passphrase` in the UPDATE when they are not empty
- `PostController.php`: When editing a protected post without changing the password, fetch the existing passphrase from the database and reuse it

**Files**: `lib/dao/PostDao.php`, `lib/controller/PostController.php`

### 9. Post Update Whitelist - Missing `post_date`

**Problem**: Editing a post throws `AppException: Sorry, unpleasant attempt detected`.

**Root Cause**: `checkPostUpdatePayload()` allows `post_modified` but not `post_date`. The edit form sends `post_date` as the field name when `post_modified` is NULL.

**Solution**: Added `'post_date'` to the whitelist in `checkPostUpdatePayload()`.

**Files**: `lib/controller/PostController.php` (line 458)

### 10. `check_form_request()` Only Validates First Key

**Problem**: Post editing silently discards title, content, and image updates.

**Root Cause**: `check_form_request()` in `lib/utility/form-security.php` had a `return` statement inside the first iteration of a `foreach` loop, so it only checked the first POST key and ignored the rest.

**Solution**: Changed the function to loop through ALL keys, always skipping `csrfToken`, `postFormSubmit`, and `MAX_FILE_SIZE`.

### 11. `deleteRecord()` with `LIMIT 1` Leaves Stale Rows

**Problem**: When a post has multiple categories, updating it silently fails with a duplicate entry error.

**Root Cause**: `PostDao::updatePost()` calls `deleteRecord("tbl_post_topic", [...])` which defaults to `LIMIT 1`, deleting only one of the category relationship rows.

**Solution**: Pass `null` as the third argument to delete all matching rows:
```php
$this->deleteRecord("tbl_post_topic", ['post_id' => (int)$cleanId], null);
```

**Files**: `lib/dao/PostDao.php` (line 243)

---

## Image/Media Issues

### 12. Empty `src` Attributes in Images

**Problem**: `<img src="">` appears in HTML output.

**Root Cause**: Wrong path construction in utility functions or missing `APP_IMAGE` constants.

**Solution**: Use the `APP_IMAGE` constants defined in `lib/common.php`:
```php
define('APP_IMAGE', APP_PUBLIC . DS . 'files' . DS . 'pictures' . DS);
```

**Important**: Never replace existing constants with hardcoded paths. Ask before modifying image utility functions.

### 13. `esc_attr()` Not Defined

**Problem**: PHP error "Call to undefined function esc_attr()".

**Root Cause**: Someone used a WordPress function (`esc_attr()`) in a theme file.

**Solution**: Replace with the project's `htmlout()` function:
```php
// Wrong (WordPress function, doesn't exist here)
esc_attr($value);
// Correct (this project's function)
htmlout($value);
```

### 14. Summernote AJAX Upload - Unauthorized

**Problem**: Clicking the image icon in the Summernote editor returns "Failed to upload image: Unauthorized".

**Root Cause**: The session cookie path (`scriptlog_auth`) was set to `/admin/`, but the image upload sends the request to an API path that doesn't start with `/admin/`.

**Solution**: Changed `COOKIE_PATH` from `APP_ADMIN` to `/` in `lib/core/Authentication.php`. Users need to log out and log back in for the new cookie to take effect.

### 15. Summernote AJAX Upload - SyntaxError

**Problem**: Image upload returns "SyntaxError: Unexpected token '<'" (it gets HTML instead of JSON).

**Root Cause**: PHP error messages (HTML) were being output before the JSON response.

**Solution**: Added output buffering in `admin/media-upload.php`.

### 16. `getimagesize()` nophoto.jpg Fallback

**Context**: The `getimagesize()` function needs a valid file path. The fallback image uses a path relative to `__DIR__`, which assumes a specific directory structure:
```php
__DIR__ . '/../../' . APP_IMAGE . 'nophoto.jpg'
```
This path breaks if you reorganize the directory structure. A constant-based path would be safer.

---

## Authentication/Session Issues

### 17. Cookie Path Not Accessible by API

**Problem**: API requests can't authenticate because the `scriptlog_auth` cookie path is `/admin/`.

**Root Cause**: `Authentication::COOKIE_PATH` was set to `APP_ADMIN` (which is `/admin/`).

**Solution**: Changed to `'/'` so the cookie is accessible to both `/admin/*` and `/api/*` paths.

### 18. Secure Installation - Sensitive Files Publicly Accessible

**Problem**: After a fresh installation, `.env` containing database credentials is publicly accessible at `https://blogware.site/.env`.

**Root Cause**: `generate_server_config()` created minimal config files without security rules.

**Solution**: Updated `.htaccess` and `nginx-rewrites.conf` to block:
- Dot files (`.env`, `.git`, `.htaccess`)
- Sensitive extensions (`.log`, `.sql`, `.bak`, `.sh`)
- Critical files (`config.php`, `composer.json`, `package.json`)
- Directories (`lib/`, `install/`, `config/`)

**Files**: `install/include/setup.php` - `generate_server_config()` function

---

## i18n/Translation Issues

### 19. Translation Editor - JSON Parse Error

**Problem**: `SyntaxError: JSON.parse: unexpected character at line 1 column 1` appears in the Translation Editor.

**Root Cause**: `TranslationController::update()` sends back JSON (`echo json_encode(...)`) but the form submits as a normal page load (not AJAX), so the JSON gets embedded into the HTML page instead of being handled by JavaScript.

**Solution**: Use session flash messages and a redirect instead of JSON:
```php
$_SESSION['status'] = 'translationUpdated';
direct_page('index.php?load=translations', 302);
```

**Files**: `lib/controller/TranslationController.php`

### 20. Database Connection Charset - Non-English Characters Show as "???"

**Problem**: Chinese, Arabic, and other non-English translations show up as "???".

**Root Cause**: The PDO database connection was missing `charset=utf8mb4` in the DSN string.

**Solution**: Make sure the DSN includes the charset:
```php
$dbc = DbFactory::connect([
    'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4',
    $user,
    $pwd
]);
```

**Files**: `lib/core/Bootstrap.php`, `lib/core/Db.php`

### 21. Missing Translation Keys in Installer

**Problem**: Raw translation keys (like `nav.privacy_policy`) appear instead of translated text after a fresh installation.

**Root Cause**: Some translation keys are missing from the `install_i18n_data()` function in `install/include/setup.php`.

**Solution**: Add the missing keys to the installer data function. For existing installations, insert them directly via SQL.

---

## API Issues

### 22. API Router Parameter Names Mismatch

**Problem**: API endpoint returns the wrong response because parameters aren't extracted correctly.

**Root Cause**: The API router's regex conversion doesn't handle named capture groups correctly.

**Solution**: Extract parameters by name from the matches array: use `$params['id']` instead of `$params[0]`.

**Files**: API router utility

### 23. API Session Initialization

**Problem**: API controllers can't access session data.

**Root Cause**: The API entry point (`api/index.php`) doesn't start the session before dispatching to controllers.

**Solution**: Added session initialization before controller dispatch in `api/index.php`.

---

## Navigation/URL Issues

### 24. Double Slashes in Redirect URLs

**Problem**: Redirect URLs contain `//` patterns.

**Root Cause**: Building paths with `dirname()` returns `/` when the input ends with `/`.

**Solution**: Normalize paths before joining them. See [Issue 4](#4-installation-double-slash-redirect).

### 25. Language Switcher URL Format Mismatch

**Problem**: The language switcher generates incorrect URLs when permalinks are enabled or disabled.

**Root Cause**: The language switcher always used the query string format, regardless of the permalink setting.

**Solution**: Use `locale_url()` when permalinks are enabled, query string format when disabled.

**Files**: `public/themes/blog/header.php`

### 26. Menu Links Don't Adapt to Permalink Format

**Problem**: Menu links stay in whatever format they were saved in, instead of switching when you change the permalink setting.

**Root Cause**: `front_navigation()` used the raw `menu_link` value without converting it to match the current URL format.

**Solution**: Added `convert_menu_link()` that converts between SEO-friendly and query string formats based on `is_permalink_enabled()`.

**Files**: `public/themes/blog/functions.php`

---

## Search Issues

### 33. Search Page Returns 404 (Apache)

**Problem**: Going to `/search` gives a 404 error on Apache, but works fine on Nginx.

**Root Cause**: The Apache `.htaccess` RewriteRule didn't include `search` in its list of allowed paths. It was also missing from the `write-htaccess.php` generator and the installer's `setup.php`.

**Solution**: Added `search` to the RewriteRule patterns in `.htaccess`, `write-htaccess.php`, and the installer. Also added the `'q'` case in `HandleRequest.php` and the `/search` pattern in `lib/main.php` for the early 404 check.

**Files**: `.htaccess`, `lib/utility/write-htaccess.php`, `install/include/setup.php`, `lib/core/HandleRequest.php`, `lib/main.php`

> **Related — permalinks disabled**: if the site's `permalink_setting` is `{"rewrite":"no"}` (query-string mode), a direct visit to `/search` returns the app's 404 by design — path-based routes are only dispatched when permalinks are enabled. Use `?q=keyword` on the app root instead, or enable permalinks in the admin panel. Search forms already handle this automatically via the `theme_search_url()` helper (`public/themes/blog/functions-post.php`), which emits `/search` when permalinks are ON and the app root `?q=` when OFF.

### 34. Search Shows Raw HTML Entities (like `&apos;` and `&quot;`)

**Problem**: Post titles show `&apos;s Guide` instead of `'s Guide`. Content shows `&quot;` instead of `"`.

**Root Cause**: The `htmlout()` function was escaping text twice. It first calls `safe_html()` (which does `htmlspecialchars`), then calls `escape_html()` (Laminas escaper) on top of that. This turns `'` into `&apos;` and then again into `&amp;apos;`, which the browser shows as literal `&apos;`.

**Solution**: Use `htmlspecialchars($text, ENT_QUOTES, 'UTF-8')` instead of `htmlout()` for regular HTML body content. The `paragraph-trim.php` docstring already recommends this pattern.

**Files**: `public/themes/blog/search.php`

### 35. Search Translation Shows `:count` Instead of the Actual Number

**Problem**: "Found :count result(s) for :keyword" appears literally instead of "Found 5 result(s) for php".

**Root Cause**: The project has two `t()` functions. The global `t()` in `functions.php` replaces `%count%` patterns (using `str_replace('%' . $param . '%', ...)`). The `I18nManager::t()` uses `:count` patterns. The template was changed to use `:count` format, but the global `t()` expects `%count%`.

**Solution**: Match the global `t()` convention. Translation strings use `%count%` and `%keyword%`. The template passes bare key names like `['count' => $totalRows]`. The function wraps them with `%` when substituting.

**Files**: `public/themes/blog/lang/en.json`, `public/themes/blog/functions.php`

### 36. Search Was Slow on Large Datasets (Full Table Scan)

**Problem**: Search was very slow when there were thousands of articles.

**Root Cause**: `SearchFinder` used `LIKE %keyword%` queries which cannot use database indexes. MySQL had to scan every single row. It was also limited to showing at most 20 or 50 results with no way to see more.

**Solution**:
- Added a FULLTEXT database index on the search columns
- Replaced `LIKE` with MySQL's full-text search (`MATCH ... AGAINST`) which uses the index and ranks results by relevance
- Added pagination with page number and results per page
- Strip special FULLTEXT operators from user input to prevent manipulation

**Files**: `plan/search-fulltext-index.sql`, `lib/core/SearchFinder.php`, `lib/controller/SearchController.php`, `public/themes/blog/search.php`, `public/themes/blog/lang/en.json`

### 37. Legacy Search Path Stored Unsanitized Keyword

**Problem**: The old search path (`?load=search`) stored the raw keyword from the URL directly into the page without running it through the security sanitizer first.

**Root Cause**: `HandleRequest::deliverQuerySearch()` put `$_GET['q']` straight into `$GLOBALS['search_keyword']` without calling `SearchFinder::sanitizeKeyword()`.

**Solution**: Use the sanitized keyword from `$results['keyword']` instead of the raw input. Also remove the `error` key from results before passing them to the template.

**Files**: `lib/core/HandleRequest.php`

### 38. Search Had No Rate Limiting

**Problem**: Someone could send hundreds of search requests per second and slow down the database.

**Root Cause**: `SearchController` never checked how many requests were coming from the same IP address.

**Solution**: Added a rate limit check at the start of `SearchController::search()` - 30 requests per minute per IP. Returns HTTP 429 (Too Many Requests) when exceeded.

**Files**: `lib/controller/SearchController.php`, `public/themes/blog/search.php`, `public/themes/blog/lang/en.json`

---

## Theme Issues

### 27. Duplicate Header/Footer in Theme Templates

**Problem**: Page content appears twice, or headers and footers show up duplicated.

**Root Cause**: Some theme template files include `call_theme_header()` or `call_theme_footer()` directly, but `HandleRequest.php` already outputs them automatically.

**Solution**: Remove `call_theme_header()` and `call_theme_footer()` from all theme template files. These are handled automatically.

### 28. Alpine.js Conflicts

**Problem**: Language switcher dropdown broken in the Valdur theme - all items visible at once, toggle doesn't work.

**Root Cause**: Alpine.js `x-show` not toggling properly, possibly from loading order issues or multiple Alpine.js instances.

**Solution**: Replaced the Alpine.js dropdown with plain JavaScript (onclick + CSS class toggle). Removed Alpine.js CDN scripts entirely.

**Files**: `public/themes/valdur/header.php`, `public/themes/valdur/assets/js/htmx-enhanced.js`

### 29. `locale_url()` Generates Unhandled Prefix Paths

**Problem**: `locale_url()` creates URLs like `/ar/some-path` but the server has no route handler for locale prefixes.

**Root Cause**: `locale_url()` in the Valdur theme used a locale prefix pattern that the server doesn't support.

**Solution**: Rewrite `locale_url()` to generate `?switch-lang=&redirect=` URLs that match the handler in `lib/main.php`.

**Files**: `public/themes/valdur/functions.php`

### 30. `hx-get` for Locale Switching Violates HTTP Semantics

**Problem**: Switching language through HTMX changes server state (session/cookie) but uses a GET request, which should be read-only.

**Root Cause**: HTMX `hx-get` sends a GET request, but changing the locale modifies server-side state.

**Solution**: Replaced `hx-get`/`hx-target="body"` with standard `<a href="?switch-lang=">` links.

**Files**: `public/themes/valdur/partials/locale-menu.php`

### 31. Search Form Conflicting HTMX Triggers

**Problem**: The search form sends multiple requests when you submit it.

**Root Cause**: Both the form's `submit` event and the input's `keyup changed delay:500ms` were active at the same time.

**Solution**: Removed all HTMX attributes from the search form and input - it's now a plain HTML form.

**Files**: `public/themes/valdur/partials/search-form.php`

### 32. Theme Toggle Button Selector Mismatch

**Problem**: The dark mode toggle button doesn't work.

**Root Cause**: The JavaScript looks for `#theme-toggle` (ID selector), but the button uses `class="theme-toggle"`.

**Solution**: Removed the `#theme-toggle` handler from JavaScript. The toggle is now handled inline via the button's `onclick` attribute.

**Files**: `public/themes/valdur/assets/js/htmx-enhanced.js`

---

## Server/Config Issues

### 39. `popper.min.js` Path Bug

**Problem**: Install layout tries to load popper.js from a path that doesn't exist.

**Root Cause**: The path was `assets/vendor/bootstrap/js/vendor/popper.min.js` (incorrect).

**Solution**: Corrected to `assets/vendor/bootstrap/js/popper.min.js`.

**Files**: `install/install-layout.php`

### 40. Config File Parameter Order

**Problem**: Installation fails with "Access denied" when creating config.php.

**Root Cause**: `write_config_file()` was called with `$dbuser` and `$dbpass` in the wrong order.

**Solution**: Corrected the argument order in `install/index.php` (line 168):
```php
// Before (wrong):
write_config_file($protocol, $server_host, $dbhost, $dbuser, $dbpass, $dbname, ...)
// After (correct):
write_config_file($protocol, $server_host, $dbhost, $dbpass, $dbuser, $dbname, ...)
```

### 41. XML Declaration Parse Error

**Problem**: PHP parse error "unexpected identifier" when generating XML export files.

**Root Cause**: `<?xml` at the start of a PHP file is interpreted as a short opening tag `<?`.

**Solution**: Use PHP string concatenation instead of inline `?>`:
```php
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<rss version="2.0">' . "\n";
```

**Files**: `lib/utility/export-wordpress.php`, `lib/utility/export-blogspot.php`

---

## Checklist - Before Reporting an Issue

1. [ ] Check PHP error logs
2. [ ] Verify database connection and table prefix
3. [ ] Clear any relevant caches (translation cache, browser cache)
4. [ ] Check cookie path - did you log out and back in after cookie path changes?
5. [ ] For API issues: verify session initialization, cookie path, and CSRF token
6. [ ] For image issues: verify APP_IMAGE constants, file permissions, and `getimagesize()` path
7. [ ] For i18n issues: verify charset=utf8mb4 in DSN and check database for missing keys
8. [ ] For theme issues: verify no duplicate header/footer calls, check browser console for JS errors
