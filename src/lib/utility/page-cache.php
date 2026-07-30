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
 * Check if a valid cache file exists for the current request.
 *
 * @return bool
 */
function page_cache_exists()
{
    if (!page_cache_is_enabled() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return false;
    }

    // Don't cache search requests
    if (isset($_GET['search']) || isset($_GET['s'])) {
        return false;
    }

    // Don't cache for logged-in users
    if (isset($_COOKIE['scriptlog_auth'])) {
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
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_COOKIE['scriptlog_auth']) && !isset($_GET['search'])) {
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
    if (page_cache_is_enabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_COOKIE['scriptlog_auth']) && !isset($_GET['search'])) {
        $content = ob_get_flush();

        if (!is_dir(APP_CACHE_DIR)) {
            mkdir(APP_CACHE_DIR, 0755, true);
        }

        $key = page_cache_key();
        $path = page_cache_path($key);

        // Only cache if the response was successful (200 OK)
        if (http_response_code() === 200) {
            file_put_contents($path, $content . "\n<!-- Scriptlog Cache Generated: " . date('Y-m-d H:i:s') . " -->");
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
