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
- [Admin Panel UI Issues](#admin-panel-ui-issues)
- [Plugin Issues](#plugin-issues)
- [Server/Config Issues](#serverconfig-issues)
- [Testing/CI Issues](#testingci-issues)

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

**Solution**: Added all 23 tables to the `knownTables` array.

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

### 19. "Direct access not permitted" After Bootstrap Changes

**Problem**: Website returns "Direct access not permitted" and becomes inaccessible after modifying bootstrap/utility files that check for `SCRIPTLOG` constant.

**Root Cause**: Files like `lib/utility/app-environment.php` and `lib/utility/dotenv-cache.php` had `defined('SCRIPTLOG') || die("Direct access not permitted")` guards. When loaded early in the bootstrap process (before `lib/common.php` defines `SCRIPTLOG`), they would trigger the guard and cause the entire request to die with that message.

**Solution**: Allow these utility files to load during bootstrap by relaxing their SCRIPTLOG guards. Define a bootstrap flag or allow loading without SCRIPTLOG being set yet:

```php
// Allow inclusion during bootstrap
if (!defined('SCRIPTLOG') && !defined('SCRIPTLOG_BOOTSTRAP')) {
    define('SCRIPTLOG_BOOTSTRAP', true);
}
```

Or simply remove/relax the guard to allow early inclusion. The `SCRIPTLOG` constant will still be defined later by `lib/common.php` for normal operation.

**Files**: `lib/utility/app-environment.php`, `lib/utility/dotenv-cache.php`

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

> **Warning**: the fix must be conditional. `lib/main.php` already starts the session through `Bootstrap::createSession()`, so an unconditional `session_set_save_handler()` call raises a warning and (before issue 51) turned every API request into a `301`. Guard it with `session_status() !== PHP_SESSION_ACTIVE` - see [Issue 51](#51-every-api-request-returns-301---sidebar-search-autocomplete-always-errors).

### 51. Every API Request Returns 301 - Sidebar Search Autocomplete Always Errors

**Problem**: The blog theme sidebar autocomplete (`public/themes/blog/sidebar.php` + `assets/js/search.js`) always shows "An error occurred during search. Please try again later." Every `/api/v1/*` request answers `301 Moved Permanently` with `Location: /api/index.php` instead of JSON. No PHP fatal appears in the browser and nothing is written to `public/log/exceptions.log`.

**Root Cause**: A three-step chain, all triggered by one harmless suppressed warning:

1. `api/index.php` called `session_set_save_handler()` on an already-active session (see [Issue 23](#23-api-session-initialization)), emitting `Session save handler cannot be changed when a session is active`.
2. `api/index.php` sets `error_reporting(0)`, so `LogError::errorHandler()` returns early. The warning therefore stays in `error_get_last()` instead of being logged.
3. The `register_shutdown_function()` in `lib/utility/whoops-error.php` promoted **any** last error - including suppressed (`@`) diagnostics - into an `ErrorException`. `MessageLog::messageException()` then answers with `direct_page('index.php', 301)` whenever `APP_DEVELOPMENT` is false, so the JSON client sees a redirect and reports a generic failure.

The same shutdown path also promoted the `@file_put_contents()` warning from the rate limiter (issue 52), which is why an unwritable cache file could break unrelated endpoints.

**Solution**:

- `lib/core/LogError.php`: added `isFatalErrorType()` (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR).
- `lib/utility/whoops-error.php`: the shutdown handler now returns early when there is no last error, when `LogError` is unavailable, or when the last error is not fatal. Warnings/notices stay with `errorHandler()`.
- `api/index.php`: session bootstrap guarded with `session_status() !== PHP_SESSION_ACTIVE`.

Do **not** "fix" this by changing `error_reporting(0)` in `api/index.php` - `MessageLog::messageError()` contains an `ini_set('display_errors', '1')` that would then leak errors into JSON responses.

**Verification**: `curl -i https://<host>/api/v1` must return `200` with no `Location` header, and `e2e/blog-search-autocomplete.spec.ts` covers the widget end to end.

**Files**: `api/index.php`, `lib/core/LogError.php`, `lib/utility/whoops-error.php`, `tests/unit/LogErrorFatalErrorTypeTest.php`, `e2e/blog-search-autocomplete.spec.ts`

### 52. Rate Limiter Counter File Not Writable

**Problem**: API and search requests intermittently fail (before issue 51 they 301-redirected), and `public/cache/rate_limit/` never accumulates counter files.

**Root Cause**: Two problems in `RateLimiter`:

- The default path was built as `APP_ROOT . DS . 'public' . DS . 'cache' ...`. `APP_ROOT` already ends with a separator, so the directory resolved to `.../public_html//public/cache/rate_limit`, which never matched the real directory.
- `writeTimestamps()` wrote with a suppressed `@file_put_contents()`. When the counter file was created by a CLI/e2e run it is owned by the shell user, so `www-data` (PHP-FPM) cannot write it. The suppressed warning was then promoted by the shutdown handler (issue 51).

**Solution**:

- Fixed the path to `APP_ROOT . 'public' . DS . 'cache' . DS . 'rate_limit'`.
- Added `isCounterWritable()`; an unwritable counter now **fails open** (the request is allowed) and logs through `error_log()` instead of raising a diagnostic. A failed write after the check is logged the same way.
- The `.htaccess` guard is only written when the directory is writable.

**Operational note**: during local/e2e runs, counter files and `public/log/exceptions.log` end up owned by your shell user, which breaks `www-data` writes. Re-own them after a CLI/e2e session:

```bash
sudo chgrp -R www-data public/cache/rate_limit public/log
sudo chmod -R g+w public/cache/rate_limit public/log
```

Counter files are disposable; the `.htaccess` inside `public/cache/rate_limit/` is not - never delete the directory wholesale.

**Files**: `lib/core/RateLimiter.php`, `tests/unit/RateLimiterTest.php`

### 55. Sidebar Search Autocomplete Fails on Nginx/DDEV - `/api/v1` Returns the Theme 404

**Problem**: On `https://scriptlog.ddev.site/?p=1` the blog theme sidebar autocomplete always shows "An error occurred during search. Please try again later." The same code works on `https://blogware.site/?p=1` (Apache). Requesting the API directly exposes the difference:

```bash
# DDEV / nginx - HTML theme 404 (or the search results page when ?q= is present)
curl -i https://scriptlog.ddev.site/api/v1
# HTTP/2 404
# content-type: text/html; charset=UTF-8

curl -i "https://scriptlog.ddev.site/api/v1/search?q=cicero"
# HTTP/2 200
# content-type: text/html; charset=UTF-8   <-- full search page, not JSON

# Apache / production - JSON
curl -i https://blogware.site/api/v1
# HTTP/1.1 200 OK
# content-type: application/json; charset=utf-8
```

**Root Cause**: The web server never routes `/api/*` to `api/index.php`.

- `public/themes/blog/header.php` always emits `api_url: '{base}/api/v1'`, and `assets/js/search.js` requests `{api_url}/search` with `dataType: 'json'`.
- Apache's `.htaccess` has an explicit `RewriteRule ^api/(.*)$ api/index.php [QSA,L]`, so production reaches the API.
- DDEV uses nginx's catch-all `try_files $uri $uri/ /index.php?$query_string;`. `/api/v1` does not exist as a file, so nginx sends it to the main front controller, which renders the theme 404. With `?q=...` the query-string router (`HandleRequest::deliverQuerySearch()`) renders the HTML search page instead - also not JSON.
- The installer-generated `nginx-rewrites.conf` (`install/include/setup.php::generate_server_config()`) omitted the `/api/` location block entirely, so fresh nginx installs had no rewrite unless an administrator later saved the permalink settings (which regenerates the file through `read_nginx_config_template()`). DDEV additionally does not `include` a project-root `nginx-rewrites.conf` by default.

The JS receives HTML where it expects JSON, the jQuery parse fails, and the generic `error` callback renders the message.

**Solution**:

1. **Front-controller fallback (fixes existing installs with no server change).** `index.php` now hands `/api/*` to `api/index.php` *before* `lib/main.php` bootstraps. The bootstrap-safe helper lives in `lib/utility/api-request.php` (`scriptlog_is_api_request()`); it strips the install base directory, ignores the query string, and matches only `/api`, `/api/`, and `/api/...` (so `/apiary` still goes through the normal dispatcher). Apache keeps using its `.htaccess` rule and never reaches this branch.
2. **Installer nginx template.** `install/include/setup.php` now writes the API location before the catch-all:

```nginx
# API routes (must precede the catch-all location /)
location ~ ^/api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

3. **Existing nginx / DDEV deployments** can either deploy the updated `index.php` (works immediately) or add the block above to their vhost. For DDEV specifically, put it in `.ddev/nginx/api.conf` and run `ddev restart`; alternatively `include` the generated `nginx-rewrites.conf` from the vhost.

Do **not** change `api_url` in `header.php` to a path-based workaround (e.g. `/api/index.php/v1`) - the API router, OpenAPI spec, HATEOAS links and external clients all use `/api/v1`.

**Verification**:

```bash
curl -i https://<host>/api/v1                 # 200 + application/json
curl -i "https://<host>/api/v1/search?q=cicero" # 200 + application/json
```

Then type at least two characters in the sidebar search box: suggestions must appear, and submitting must navigate to the permalink-aware results page. `tests/unit/ApiFrontControllerHandoffTest.php` covers the path matching and the front-controller ordering; `e2e/blog-search-autocomplete.spec.ts` covers the widget end to end.

**Related**: [Issue 51](#51-every-api-request-returns-301---sidebar-search-autocomplete-always-errors) is the same symptom with a different status code (`301` from the error handler); [Issue 23](#23-api-session-initialization) covers the API session bootstrap.

**Files**: `index.php`, `lib/utility/api-request.php`, `lib/utility-loader.php`, `install/include/setup.php`, `tests/unit/ApiFrontControllerHandoffTest.php`

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

### 53. Sidebar Autocomplete Keyboard Navigation Lost the Combobox

**Problem**: In the blog theme sidebar autocomplete, pressing `ArrowDown` once highlighted the first suggestion, but the next `ArrowDown`, `ArrowUp` or `Escape` did nothing. Screen readers also lost the combobox context.

**Root Cause**: `moveActive()` in `public/themes/blog/assets/js/search.js` called `link.focus()` on the highlighted suggestion and then cleared `aria-activedescendant`. DOM focus moved off the `role="combobox"` input, so subsequent key events targeted the `<a>` instead of the input's `keydown` handler.

**Solution**: Follow the WAI-ARIA 1.2 combobox pattern instead of moving focus:

- Each `.search-result-item` now carries a stable `id` (`search-results-option-N`, prefixed with the listbox id).
- `moveActive()` keeps focus on the input and points `aria-activedescendant` at the active option; `hideResults()`, `showLoading()` and both render paths clear the attribute.
- New `scrollActiveIntoView()` keeps the active option visible inside the `max-height: 400px; overflow-y: auto` popup - previously `link.focus()` scrolled it implicitly.

Because the served asset is `search.min.js` with an `integrity="sha384-..."` attribute, the fix had to ship as one unit:

```bash
node --check public/themes/blog/assets/js/search.js   # syntax gate first
php tmp/minify.php blog                               # scoped rebuild + hash sync
```

`tmp/minify.php` accepts an optional theme name so a rebuild no longer rewrites every theme's `.min.*` files and integrity hashes, and it refreshes `footer.php` automatically via `sync_integrity_hashes()`.

**Verification**: `e2e/blog-search-autocomplete.spec.ts` asserts focus retention, `aria-activedescendant` tracking, wrap-around, Escape after several moves and active-option scrolling (30/30 across chromium/firefox/webkit). Plan: `specs/blog-search-autocomplete.md` §5.5.

**Files**: `public/themes/blog/assets/js/search.js`, `public/themes/blog/assets/js/search.min.js`, `public/themes/blog/footer.php`, `tmp/minify.php`, `e2e/blog-search-autocomplete.spec.ts`

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

## Admin Panel UI Issues

### 44. List-Table Footer Mismatch Between Admin Pages

**Problem**: The posts list (`?load=posts`) shows a DataTables sort indicator on the Delete column in `<thead>`/`<tfoot>`, while the users list (`?load=users`) does not.

**Root Cause**: The shared DataTables init in `admin_footer()` hardcodes `"targets": [0, 4, 5]` as non-orderable. That fits the 6-column users table but not the 7-column posts table, whose Delete column (index 6) stays orderable.

**Solution**: Use negative indices - `"targets": [0, -1, -2]` (supported since DataTables 1.10; bundled version is 1.10.19) - so the row-number column plus the last two action columns are non-orderable on every admin list table regardless of column count.

**Files**: `admin/admin-layout.php` (`admin_footer()`)

### 45. Dark Gap Below the Admin Footer on Short Pages

**Problem**: On short pages (e.g. posts with 1 row) the white `.main-footer` floats above the viewport bottom, exposing the dark `#122430` `.wrapper` background beneath it. Taller pages reach the bottom, so footers look inconsistent.

**Root Cause**: Nothing stretches `.content-wrapper` when content is shorter than the viewport (AdminLTE's layout script only sets a `min-height`).

**Solution**: Pure-CSS sticky footer - flex column on `.wrapper`, `flex: 1 0 auto` on `.content-wrapper`. Safe because `.main-sidebar` is absolute and `.control-sidebar-bg` is fixed (both out of flex flow). Long pages are unaffected.

**Files**: `admin/assets/dist/css/skins/scriptlog-skin.css`

### 46. Scrollbar Only on Posts at Laptop Heights

**Problem**: At window heights ~640-790px the posts page scrolls while users/templates do not.

**Root Cause**: On the posts page the Posts treeview submenu is expanded (+3 rows), making `.main-sidebar` taller than the viewport. AdminLTE's layout script then stretches `.content-wrapper` to the sidebar height, pushing the footer below the fold.

**Solution**: Cap the sidebar at the viewport (`max-height: 100vh`, `overflow: hidden`) and scroll the menu internally (`max-height: calc(100vh - 50px)`, `overflow-y: auto` on `> .sidebar`). Scoped to `min-width: 768px` and `body:not(.sidebar-collapse)` so mini-mode hover flyouts and the off-canvas mobile sidebar keep working.

**Files**: `admin/assets/dist/css/skins/scriptlog-skin.css`

### 47. Admin CSS Changes Not Visible After Deploy (Stale Cache)

**Problem**: Admin CSS fixes appear to "not work" - the browser keeps rendering the old styles.

**Root Cause**: `scriptlog-skin.css` is served with `Cache-Control: max-age=2592000` (30 days) and `admin-layout.php` loaded it with no version query string, so browsers reuse the stale copy without revalidating.

**Solution**: The skin link now carries `?v=` derived from `filemtime()` (falling back to `APP_VERSION`), so every edit busts the cache automatically. Same `?v=` convention as the login layout (`admin/login-layout.php`).

**Files**: `admin/admin-layout.php` (`admin_header()`)

---

## Plugin Issues

### 48. Plugin Activation Refused - "Plugin files failed the integrity check"

**Problem**: Clicking Enable on an installed plugin redirects back to the plugin list with `Error: Plugin files failed the integrity check, so activation was refused.`

**Root Cause**: Phase 2 of the RCE remediation (`plan/RCE_REMEDIATION_OCTOBER2026_PLAN.md` §2.7) records a SHA-256 manifest over `plugin.ini` plus every PHP file at install time (`tbl_plugin.plugin_manifest`) and re-verifies it before `enable_plugin()` runs. The check fails when:
- a plugin file was edited, added, or deleted after installation,
- `plugin_manifest` is `NULL` because the plugin was installed before the column existed,
- `plugin_directory` is blank or does not match the directory on disk.

The refusal is intentional: modified plugin code would otherwise execute as the web server user.

**Solution**: Re-install the plugin from a trusted archive (delete the plugin first — the upload form rejects an archive whose directory already exists). If you have reviewed the current files and they are what you intend to run, record a fresh digest explicitly:

```bash
php install/migrate-plugin-manifest.php --backfill
```

Check `storage/logs/plugin-audit-*.jsonl` for `plugin_manifest_rejected` entries (plugin id, directory, recorded manifest, actor IP).

**Files**: `lib/utility/plugin-manifest.php`, `lib/service/PluginService.php` (`isPluginManifestValid()`, `activateInstalledPlugin()`), `lib/controller/PluginController.php` (`saveInstalledPlugin()`)

### 49. Plugins Missing After Upgrade - `plugin_manifest` Column Does Not Exist

**Problem**: Uploading or listing plugins fails with `Unknown column 'plugin_manifest' in 'field list'` after upgrading an existing installation.

**Root Cause**: `install/include/dbtable.php` only runs during a fresh install, so existing databases keep the old `tbl_plugin` definition.

**Solution**: Run the versioned migration once per installation:

```bash
php install/migrate-plugin-manifest.php              # add the column only
php install/migrate-plugin-manifest.php --backfill   # also digest installed plugins
```

The migration is CLI-only and idempotent (safe to run twice). Without `--backfill` the existing plugin rows stay `NULL` and refuse activation until re-installed or backfilled — see issue 48.

**Files**: `install/migrate-plugin-manifest.php`, `install/include/dbtable.php`

### 50. Manager Cannot Enable, Disable, or Delete Plugins

**Problem**: A `manager` account no longer sees working plugin actions.

**Root Cause**: Plugin mutation was restricted to administrators in the Phase 2 RCE remediation — `PLUGIN_MANAGE` maps to `administrator` in `Authentication::checkAccessByLevel()`. This is by design: activating a plugin loads arbitrary PHP into the request.

**Solution**: Perform plugin management with an administrator account. `plugin_level` on the plugin itself is only an admin-UI visibility hint and does not grant management rights (see `docs/dev-docs/PLUGIN_DEVELOPER_GUIDE.md` §2b).

**Files**: `lib/core/Authentication.php`, `lib/dao/PluginDao.php` (`setMenuPlugin()`)

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

## Testing/CI Issues

### 42. Playwright Default Suite Fails on RestoBlog Theme Specs

**Problem**: `npx playwright test` fails on `e2e/restoblog/**` specs during an ordinary run - pages render the `blog` theme instead of RestoBlog.

**Root Cause**: RestoBlog is a premium theme (`theme_type='premium'`) with no active license in the e2e database, so `premium_theme_license_supports_render()` falls back to the `blog` theme for every request. RestoBlog already owns a dedicated Playwright config (`playwright.restoblog.config.js`) that flips the active theme in globalSetup, but the default `playwright.config.js` only ignored `**/tastybites/**`.

**Solution**: Extended `testIgnore` in `playwright.config.js` to `['**/tastybites/**', '**/restoblog/**']`. Theme suites run under their own config (`npx playwright test --config=playwright.restoblog.config.js`), and RestoBlog specs additionally need an active license/theme seed in the e2e database to render.

**Files**: `playwright.config.js`, `playwright.restoblog.config.js`, `lib/utility/theme-caller.php`

### 43. OpenAPI Verification Test Fails - `API_OPENAPI.json` Missing

**Problem**: Nearly the whole `OpenApiSpecVerificationTest` suite fails (JSON file exists, YAML-and-JSON coherence checks, etc.) even though `API_OPENAPI.yaml` is fine.

**Root Cause**: `docs/dev-docs/API_OPENAPI.json` - previously tracked - was missing from the repository. The test loads it with `file_get_contents()`, so every JSON-backed assertion failed. The file only reappeared as an untracked artifact during spec-generation runs.

**Solution**: Regenerated and committed the JSON sibling of the YAML (36 paths, 54 schemas). Also aligned the two server-coherence assertions with the deliberate "single relative `url: /api/v1` server" design (commit `7d03e1af`) - the test previously required at least two absolute placeholder-domain servers.

**Files**: `docs/dev-docs/API_OPENAPI.json`, `docs/dev-docs/API_OPENAPI.yaml`, `tests/unit/OpenApiSpecVerificationTest.php`

### 54. E2E/Unit Runs Overwrite the Committed Translation Cache

**Problem**: After running `npx playwright test` or the PHPUnit suite, `git status` shows `public/files/cache/translations/en.json` reduced to a handful of keys and `es.json`/`id.json` filled with unrelated strings.

**Root Cause**: `TranslationLoader` regenerates `public/files/cache/translations/{locale}.json` from whichever database is active. Suites that run against `blogware_e2e` (which seeds only a subset of translations) overwrite the cache produced from the development database. Because the cache is tracked in git, the test data leaks into the diff.

**Solution**: Do not commit the regenerated cache after a test run - restore it:

```bash
git checkout -- public/files/cache/translations/
```

The cache is rebuilt from the active database on the next request (TTL 3600s). If you intentionally changed translations, regenerate from the development database first, then commit.

**Files**: `lib/core/TranslationLoader.php`, `public/files/cache/translations/`

> **Related**: CLI/e2e runs also leave `public/log/exceptions.log` modified and owned by your shell user. Restore it the same way and re-own it to `www-data` if production logging matters (see [Issue 52](#52-rate-limiter-counter-file-not-writable)).

---

## Checklist - Before Reporting an Issue

1. [ ] Check PHP error logs
2. [ ] Verify database connection and table prefix
3. [ ] Clear any relevant caches (translation cache, browser cache)
4. [ ] Check cookie path - did you log out and back in after cookie path changes?
5. [ ] For API issues: `curl -i /api/v1` returns `200` with `Content-Type: application/json` (not `301`, and not an HTML theme page), session initialization is idempotent, cookie path is `/`, and the CSRF token is valid. On nginx/DDEV also check [issue 55](#55-sidebar-search-autocomplete-fails-on-nginxddev---apiv1-returns-the-theme-404)
6. [ ] For image issues: verify APP_IMAGE constants, file permissions, and `getimagesize()` path
7. [ ] For i18n issues: verify charset=utf8mb4 in DSN and check database for missing keys
8. [ ] For theme issues: verify no duplicate header/footer calls, check browser console for JS errors
9. [ ] After a CLI/e2e run: restore `public/files/cache/translations/` and re-own `public/cache/rate_limit/` + `public/log/` to `www-data` (issues 52 and 54)
