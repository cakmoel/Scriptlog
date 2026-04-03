<?php

/**
 * API Entry Point
 *
 * Blogware RESTful API
 *
 * This is the entry point for all API requests.
 * It handles request routing, authentication, and response formatting.
 *
 * API Version: 1.0
 * Base URL: /api/v1/
 *
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

// Prevent direct access to API
define('SCRIPTLOG', hash('sha256', 'BLOGWARE_API_ACCESS'));
define('API_VERSION', 'v1');
define('API_BASE_PATH', '/api/v1');

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/../config.php')) {
    $config = require __DIR__ . '/../config.php';
}

// Set CORS headers for cross-origin requests
$allowed_origins = !empty($config['api']['allowed_origins']) ? $config['api']['allowed_origins'] : '';
$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_list = array_filter(array_map('trim', explode(',', $allowed_origins)));

if (in_array($request_origin, $allowed_list, true)) {
    header('Access-Control-Allow-Origin: ' . $request_origin);
    header('Access-Control-Allow-Credentials: true');
} elseif (!empty($allowed_list[0])) {
    header('Access-Control-Allow-Origin: ' . $allowed_list[0]);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With');
header('Access-Control-Max-Age: 3600');
header('Content-Type: application/json');
header('X-API-Version: ' . API_VERSION);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Set error reporting for API (production should disable this)
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering for clean JSON responses
ob_start();

// Load required core files
require_once __DIR__ . '/../lib/main.php';
require_once __DIR__ . '/../lib/core/ApiAuth.php';
require_once __DIR__ . '/../lib/core/ApiResponse.php';
require_once __DIR__ . '/../lib/core/ApiRouter.php';
require_once __DIR__ . '/../lib/controller/ApiController.php';
require_once __DIR__ . '/../lib/controller/api/PostsApiController.php';
require_once __DIR__ . '/../lib/controller/api/CategoriesApiController.php';
require_once __DIR__ . '/../lib/controller/api/CommentsApiController.php';
require_once __DIR__ . '/../lib/controller/api/ArchivesApiController.php';
require_once __DIR__ . '/../lib/controller/api/GdprApiController.php';
require_once __DIR__ . '/../lib/controller/api/LanguagesApiController.php';
require_once __DIR__ . '/../lib/controller/api/TranslationsApiController.php';
require_once __DIR__ . '/../lib/controller/api/SearchApiController.php';

// Initialize API
try {
    // Get request method and URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Remove base path from URI
    $uri = str_replace(API_BASE_PATH, '', $uri);
    $uri = trim($uri, '/');

    // Parse query string for pagination and filters
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $queryParams);

    // Initialize API Router
    $router = new ApiRouter();

    // Register API routes
    // Posts API
    $router->get('posts', 'PostsApiController@index');
    $router->get('posts/([0-9]+)', 'PostsApiController@show');
    $router->get('posts/([0-9]+)/comments', 'PostsApiController@comments');
    $router->post('posts', 'PostsApiController@store');
    $router->put('posts/([0-9]+)', 'PostsApiController@update');
    $router->delete('posts/([0-9]+)', 'PostsApiController@destroy');

    // Categories/Topics API
    $router->get('categories', 'CategoriesApiController@index');
    $router->get('categories/([0-9]+)', 'CategoriesApiController@show');
    $router->get('categories/([0-9]+)/posts', 'CategoriesApiController@posts');
    $router->post('categories', 'CategoriesApiController@store');
    $router->put('categories/([0-9]+)', 'CategoriesApiController@update');
    $router->delete('categories/([0-9]+)', 'CategoriesApiController@destroy');

    // Comments API
    $router->get('comments', 'CommentsApiController@index');
    $router->get('comments/([0-9]+)', 'CommentsApiController@show');
    $router->post('comments', 'CommentsApiController@store');
    $router->put('comments/([0-9]+)', 'CommentsApiController@update');
    $router->delete('comments/([0-9]+)', 'CommentsApiController@destroy');

    // Archives API
    $router->get('archives', 'ArchivesApiController@index');
    $router->get('archives/([0-9]{4})', 'ArchivesApiController@year');
    $router->get('archives/([0-9]{4})/([0-9]{2})', 'ArchivesApiController@month');

    // Search API
    $router->get('search', 'SearchApiController@index');
    $router->get('search/posts', 'SearchApiController@posts');
    $router->get('search/pages', 'SearchApiController@pages');

    // GDPR API
    $router->post('gdpr/consent', 'GdprApiController@consent');
    $router->get('gdpr/consent', 'GdprApiController@getConsentStatus');

    // Languages API
    $router->get('languages', 'LanguagesApiController@index');
    $router->get('languages/active', 'LanguagesApiController@index');
    $router->get('languages/default', 'LanguagesApiController@default');
    $router->get('languages/([a-z]{2})', 'LanguagesApiController@show');
    $router->post('languages', 'LanguagesApiController@store');
    $router->put('languages/([a-z]{2})', 'LanguagesApiController@update');
    $router->delete('languages/([a-z]{2})', 'LanguagesApiController@destroy');
    $router->post('languages/([a-z]{2})/default', 'LanguagesApiController@setDefault');

    // Translations API
    $router->get('translations/([a-z]{2})', 'TranslationsApiController@index');
    $router->get('translations/([a-z]{2})/([a-zA-Z0-9._-]+)', 'TranslationsApiController@show');
    $router->post('translations/([a-z]{2})', 'TranslationsApiController@store');
    $router->put('translations/([0-9]+)', 'TranslationsApiController@update');
    $router->delete('translations/([0-9]+)', 'TranslationsApiController@destroy');
    $router->get('translations/([a-z]{2})/export', 'TranslationsApiController@export');
    $router->post('translations/([a-z]{2})/import', 'TranslationsApiController@import');
    $router->post('translations/([a-z]{2})/cache', 'TranslationsApiController@cache');

    // API OpenAPI spec endpoint
    $router->get('openapi.json', function ($params) {
        $specFile = __DIR__ . '/../docs/API_OPENAPI.json';
        if (file_exists($specFile)) {
            header('Content-Type: application/json');
            header('X-API-Version: ' . API_VERSION);
            readfile($specFile);
            exit;
        }
        ApiResponse::notFound('OpenAPI specification not found');
    });

    // API Info endpoint
    $router->get('', 'ApiController@info');
    $router->get('/', 'ApiController@info');

    // Dispatch the request
    $router->dispatch($method, $uri, $queryParams);
} catch (\Throwable $e) {
    // Handle any uncaught exceptions
    ApiResponse::error($e->getMessage(), 500, 'INTERNAL_SERVER_ERROR');
}

// Flush output buffer
ob_end_flush();
