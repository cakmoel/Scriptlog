<?php
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