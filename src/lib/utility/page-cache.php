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
 * Check if a valid cache file exists for the current request.
 *
 * @return bool
 */
function page_cache_exists()
{
    if (APP_CACHE !== true || $_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    if (file_exists($path) && (time() - filemtime($path)) < APP_CACHE_LIFETIME) {
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
    if (APP_CACHE === true && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_COOKIE['scriptlog_auth']) && !isset($_GET['search'])) {
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
    if (APP_CACHE === true && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_COOKIE['scriptlog_auth']) && !isset($_GET['search'])) {
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
