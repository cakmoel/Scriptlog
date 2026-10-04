<?php

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
function page_cache_exists()
{
    if (!page_cache_is_enabled() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return false;
    }

    // Don't cache search requests
    if (page_cache_is_search_request()) {
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
 * @return void
 */
function page_cache_serve()
{
    $key = page_cache_key();
    $path = page_cache_path($key);

    if (file_exists($path)) {
        header('X-Scriptlog-Cache: Hit');
        readfile($path);
        exit;
    }
}

/**
 * Start capturing the page output for caching.
 *
 * @return void
 */
function page_cache_start()
{
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && page_cache_is_anonymous() && !page_cache_is_search_request()) {
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
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && page_cache_is_anonymous() && !page_cache_is_search_request()) {
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
