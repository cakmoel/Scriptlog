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

// Front-controller API hand-off.
//
// Apache (.htaccess) and the generated nginx include send /api/* directly to
// api/index.php. Servers that instead route every unknown path to this front
// controller (DDEV/nginx without the include, PHP's built-in server, default
// LiteSpeed) would render the theme 404 for /api/v1 - which surfaces in the
// sidebar autocomplete as "An error occurred during search." Dispatch before
// lib/main.php so api/index.php keeps its own bootstrap order.
//
// __DIR__ is passed explicitly because SCRIPT_NAME is not always the front
// controller: PHP's built-in server reports the nearest matching file (for
// /api/v1 it reports /api/index.php) while nginx/Apache report /index.php.
require_once dirname(__FILE__) . '/lib/utility/api-request.php';

if (scriptlog_is_api_request(null, null, __DIR__)) {
    require dirname(__FILE__) . '/api/index.php';
    exit;
}

ob_start();

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