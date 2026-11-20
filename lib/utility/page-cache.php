<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Page Cache Utility
 * Provides a simple file-based full-page cache for Scriptlog.
 *
 * @category Utility
 * @author   M.Noermoehammad
 * @license  MIT
 */

/**
 * Generate a cache key for the current request.
 *
 * @return string
 */
function page_cache_key()
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return md5($protocol . $host . $uri);
}

/**
 * Get the full path to the cache file.
 *
 * @param string $key
 * @return string
 */
function page_cache_path($key)
{
    return APP_CACHE_DIR . $key . '.html';
}

/**
 * Check whether the full-page cache is enabled.
 *
 * The cache is active when the APP_CACHE constant is enabled (hard
 * kill-switch) OR the cache_enabled setting is '1'. Because app_settings()
 * is memoized per request, this check costs nothing after bootstrap.
 *
 * @return bool
 */
function page_cache_is_enabled()
{
    if (defined('APP_CACHE') && APP_CACHE === true) {
        return true;
    }

    return (function_exists('app_setting') && app_setting('cache_enabled', '0') === '1');
}

/**
 * Get the effective cache lifetime in seconds.
 *
 * Precedence: cache_lifetime setting (if positive integer) then the
 * APP_CACHE_LIFETIME constant, defaulting to 3600.
 *
 * @return int
 */
function page_cache_ttl()
{
    $setting = function_exists('app_setting') ? app_setting('cache_lifetime', '') : '';

    if (is_string($setting) && ctype_digit($setting) && (int)$setting > 0) {
        return (int)$setting;
    }

    return (defined('APP_CACHE_LIFETIME')) ? APP_CACHE_LIFETIME : 3600;
}

/**
 * Check whether the current visitor is anonymous for caching purposes.
 *
 * C1: the old check looked only at the `scriptlog_auth` cookie, but
 * non-"remember me" logins are session-only (Session::scriptlog_session_id).
 * Remember-me logins additionally set `scriptlog_validator`/`selector`.
 * Any of these marks an authenticated user whose responses must never be
 * cached under a URI key shared with anonymous visitors.
 *
 * @return bool
 */
function page_cache_is_anonymous()
{
    if (isset($_COOKIE['scriptlog_auth']) || isset($_COOKIE['scriptlog_validator']) || isset($_COOKIE['scriptlog_selector'])) {
        return false;
    }

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['scriptlog_session_id'])) {
        return false;
    }

    return true;
}

/**
 * Check whether the current request is a search request.
 *
 * H1: search-result pages embed the attacker-influenced keyword and must
 * never be cached cross-user. Covers the `/search` route, the `?q=`
 * query-string mode, and the legacy `search`/`s` parameters, consistently
 * for exists/start/finish.
 *
 * @return bool
 */
function page_cache_is_search_request()
{
    if (isset($_GET['search']) || isset($_GET['s']) || isset($_GET['q'])) {
        return true;
    }

    $path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    if (is_string($path) && preg_match('#/search/?$#', $path)) {
        return true;
    }

    return false;
}

/**
 * Check whether the current request is an HTMX partial request.
 *
 * H2: HTMX clients request HTML fragments (and expect HX-* response
 * semantics) that differ from full-page renders. The cache key covers
 * only protocol+host+URI, so a fragment request must never be served a
 * stored full page, and a fragment render must never be stored over it.
 * Guarded consistently by exists/serve/start/finish.
 *
 * @return bool
 */
function page_cache_is_htmx_request()
{
    return isset($_SERVER['HTTP_HX_REQUEST']);
}
function page_cache_exists()
{
    if (!page_cache_is_enabled() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return false;
    }

    // Don't cache search requests
    if (page_cache_is_search_request()) {
        return false;
    }

    // Don't serve cached full pages to HTMX fragment requests
    if (page_cache_is_htmx_request()) {
        return false;
    }

    // Don't cache for logged-in users
    if (!page_cache_is_anonymous()) {
        return false;
    }

    $key = page_cache_key();
    $path = page_cache_path($key);

    if (file_exists($path) && (time() - filemtime($path)) < page_cache_ttl()) {
        return true;
    }

    return false;
}

/**
 * Serve the cached file and exit.
 *
 * The stored HTML embeds per-request dynamic values (CSP nonces on inline
 * scripts, per-session one-time CSRF tokens on public forms). Serving it
 * verbatim would break inline scripts (nonce mismatch against the current
 * response's CSP header) and form submissions (token unknown to the
 * current visitor's session). D4: both are rewritten to values minted for
 * the current request immediately before output.
 *
 * @return void
 */
function page_cache_serve()
{
    if (page_cache_is_htmx_request()) {
        return;
    }

    $key = page_cache_key();
    $path = page_cache_path($key);

    if (file_exists($path)) {
        $content = file_get_contents($path);

        if (is_string($content)) {
            header('X-Scriptlog-Cache: Hit');
            // D5: drop the storage marker so a hit is byte-identical to the
            // original (uncached) response — ab Length-mismatch noise stays
            // at zero and Content-Length is stable across hits.
            $content = preg_replace(
                '/\n?<!-- Scriptlog Cache Generated: [^>]*-->\s*$/',
                '',
                $content
            );
            echo page_cache_refresh_dynamic_markup($content);
            exit;
        }
    }
}

/**
 * Rewrite per-request dynamic markup inside cached HTML for the current visitor.
 *
 * Two substitutions are performed:
 *
 * 1. Every `nonce="..."` attribute is set to the current request's CSP_NONCE,
 *    so inline scripts match the `script-src 'nonce-...'` header emitted for
 *    this response (Bootstrap ran before the cached page is served).
 * 2. Every hidden `<input ... name="csrf">` (the public comment form token,
 *    stored under the `comment_form` session key by the themes' block_csrf())
 *    receives a fresh one-time token generated for the current visitor's
 *    session.
 *
 * Substitutions are no-ops when the markup is absent, so pages without
 * inline scripts or comment forms pass through unchanged.
 *
 * @param string $html Cached page HTML
 * @param callable|null $csrfTokenProvider Optional token factory
 *        signature: callable(string $formKey): string. Defaults to
 *        generate_form_token('comment_form', 32). Injectable for tests.
 * @return string HTML with dynamic values refreshed for this request
 */
function page_cache_refresh_dynamic_markup($html, $csrfTokenProvider = null)
{
    if (!is_string($html) || $html === '') {
        return is_string($html) ? $html : '';
    }

    // 1. Refresh CSP nonces so inline scripts match the current response.
    if (defined('CSP_NONCE') && CSP_NONCE !== '') {
        $refreshedMarkup = preg_replace_callback(
            '/\bnonce="[^"]*"/',
            static function () {
                return 'nonce="' . CSP_NONCE . '"';
            },
            $html
        );

        if (is_string($refreshedMarkup)) {
            $html = $refreshedMarkup;
        }
    }

    // 2. Refresh the public comment-form CSRF token for this visitor's session.
    if ($csrfTokenProvider === null) {
        $csrfTokenProvider = static function ($formKey) {
            return (function_exists('generate_form_token'))
                ? generate_form_token($formKey, 32)
                : '';
        };
    }

    $refreshedMarkup = preg_replace_callback(
        '/<input[^>]*\bname="csrf"[^>]*>/',
        static function (array $matches) use ($csrfTokenProvider) {
            $tag = $matches[0];
            $token = (string)$csrfTokenProvider('comment_form');

            if ($token === '') {
                return $tag;
            }

            $updatedTag = preg_replace(
                '/\bvalue="[^"]*"/',
                'value="' . $token . '"',
                $tag,
                1
            );

            return is_string($updatedTag) ? $updatedTag : $tag;
        },
        $html
    );

    if (is_string($refreshedMarkup)) {
        $html = $refreshedMarkup;
    }

    return $html;
}

/**
 * Start capturing the page output for caching.
 *
 * @return void
 */
function page_cache_start()
{
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !page_cache_is_htmx_request() && page_cache_is_anonymous() && !page_cache_is_search_request()) {
        ob_start();
    }
}

/**
 * Capture the output and save it to the cache file.
 *
 * @return void
 */
function page_cache_finish()
{
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !page_cache_is_htmx_request() && page_cache_is_anonymous() && !page_cache_is_search_request()) {
        $content = ob_get_flush();

        if (!is_dir(APP_CACHE_DIR)) {
            mkdir(APP_CACHE_DIR, 0755, true);
        }

        $key = page_cache_key();
        $path = page_cache_path($key);

        // Only cache if the response was successful (200 OK)
        if (http_response_code() === 200) {
            // M3: LOCK_EX avoids torn cache files under concurrent writes.
            file_put_contents($path, $content . "\n<!-- Scriptlog Cache Generated: " . date('Y-m-d H:i:s') . " -->", LOCK_EX);
        }
    }
}

/**
 * Clear all cached pages.
 *
 * @return void
 */
function page_cache_clear()
{
    if (is_dir(APP_CACHE_DIR)) {
        $files = glob(APP_CACHE_DIR . '*.html');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
