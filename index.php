<?php

ob_start();

/**
 * index.php file
 * 
 * @category index.php file designed as front controller
 * @author   M.Noermoehammad
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
require dirname(__FILE__) . '/lib/main.php';

// lib/main.php only provides $app when config.php exists (otherwise it
// redirects to the installer and exits). Bail out with a clean 500 on a
// broken deploy that has neither config.php nor install/.
if (!isset($app)) {
    http_response_code(500);
    exit('Application is not configured. Please run the installer.');
}

// Check if a valid cache file exists and serve it
if (function_exists('page_cache_exists') && page_cache_exists()) {
    page_cache_serve();
}

// Start capturing output if caching is enabled
if (function_exists('page_cache_start')) {
    page_cache_start();
}

$app->dispatcher->dispatch();

// Capture the output and save it to the cache if it was a miss
if (function_exists('page_cache_finish')) {
    page_cache_finish();
}