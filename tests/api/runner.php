<?php
/**
 * API Test Subprocess Runner
 *
 * Executes a single API controller action (or routes a request through a
 * fresh ApiRouter) inside an isolated PHP process and reports the resulting
 * HTTP status code, response body, headers, and rate-limit state.
 *
 * This runner exists because ApiResponse::send() terminates the process
 * (exit) — a controller action cannot be invoked safely in the PHPUnit
 * process itself. Each invocation is a clean, isolated request.
 *
 * Usage:
 *   php tests/api/runner.php <env-json-path>
 *
 * The environment JSON supports two execution modes:
 *
 *   1. Direct controller action:
 *        {
 *          "controller": "Scriptlog\\Controller\\Api\\PostsApiController",
 *          "action": "index",
 *          "params": { "id": "1" }
 *        }
 *
 *   2. Router dispatch:
 *        {
 *          "dispatch": { "method": "GET", "uri": "posts", "query": {} },
 *          "routes": [ { "method": "get", "pattern": "posts", "handler": "PostsApiController@index" } ]
 *        }
 *
 * Both modes accept the following optional keys:
 *   - "server":       $_SERVER overrides (REQUEST_METHOD, HTTP_ACCEPT, ...)
 *   - "get"/"post"/"files": superglobal values to install
 *   - "api_key":      plaintext API key to send as the X-API-Key header
 *   - "session_user": associative array passed to ApiAuth::setSessionUser()
 *   - "session":      $_SESSION values to install before dispatch
 *
 * Output protocol (STDOUT):
 *   API_RESULT_START
 *   <single-line JSON: {"code":<int>,"headers":[...],"body":"...","rate":...}>
 *
 * @package Scriptlog\Tests
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$envFile = $argv[1] ?? '';

if ($envFile === '' || !is_file($envFile)) {
    fwrite(STDERR, "Missing or invalid environment file argument\n");
    exit(1);
}

$env = json_decode((string)file_get_contents($envFile), true);

if (!is_array($env)) {
    fwrite(STDERR, "Invalid environment file\n");
    exit(1);
}

// Load the API test bootstrap (utilities, aliases, DB connection).
require_once __DIR__ . '/../bootstrap_api.php';

// DAOs call Db::dbQuery(), so 'dbc' must hold a Db wrapper, not a raw PDO.
$config = get_test_config();
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['name']
);
$db = new \Scriptlog\Core\Db([$dsn, $config['db']['user'], $config['db']['pass']]);
\Scriptlog\Core\Registry::set('dbc', $db);
\Scriptlog\Core\Registry::set('db', $db);
$GLOBALS['__test_prefix'] = true;
\Scriptlog\Core\Registry::set('app_url', $config['app']['url']);

// Suppress warnings/deprecations so they cannot pollute the captured body.
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Install server environment.
$serverDefaults = [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/api/v1/',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_USER_AGENT' => 'PHPUnit',
    'REMOTE_ADDR' => '127.0.0.1',
    'SERVER_NAME' => 'localhost',
    'SERVER_PROTOCOL' => 'HTTP/1.1',
    'SCRIPT_NAME' => '/api/index.php',
    'SCRIPT_FILENAME' => __DIR__ . '/../../api/index.php',
];

foreach ($env['server'] ?? [] as $key => $value) {
    $_SERVER[$key] = $value;
}

foreach ($serverDefaults as $key => $value) {
    if (!isset($_SERVER[$key])) {
        $_SERVER[$key] = $value;
    }
}

// Install optional authentication.
if (!empty($env['api_key'])) {
    $_SERVER['HTTP_X_API_KEY'] = $env['api_key'];
}

if (isset($env['session_user']) && is_array($env['session_user'])) {
    \Scriptlog\Core\ApiAuth::setSessionUser($env['session_user']);
}

if (isset($env['session']) && is_array($env['session'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    foreach ($env['session'] as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

// Install superglobals.
$_GET = $env['get'] ?? [];
$_POST = $env['post'] ?? [];
$_FILES = $env['files'] ?? [];

// Capture the response on shutdown (exit() from ApiResponse::send()).
ob_start();
register_shutdown_function(function () {
    $code = http_response_code();
    $headers = headers_list();
    $body = (string)ob_get_clean();
    $rate = isset($GLOBALS['_api_rate_result']) ? $GLOBALS['_api_rate_result'] : null;

    fwrite(STDOUT, "API_RESULT_START\n");
    fwrite(STDOUT, json_encode([
        'code' => $code,
        'headers' => $headers,
        'body' => $body,
        'rate' => $rate,
    ]) . "\n");
});

// Router dispatch mode.
if (isset($env['dispatch']) && is_array($env['dispatch'])) {
    $router = new \Scriptlog\Core\ApiRouter();

    foreach ($env['routes'] ?? [] as $route) {
        $method = strtolower($route['method'] ?? '');
        $pattern = $route['pattern'] ?? '';
        $handler = $route['handler'] ?? '';

        if (method_exists($router, $method)) {
            $router->{$method}($pattern, $handler);
        }
    }

    $dispatch = $env['dispatch'];
    $router->dispatch(
        $dispatch['method'] ?? 'GET',
        $dispatch['uri'] ?? '',
        $dispatch['query'] ?? []
    );
    return;
}

// Direct controller action mode.
$controller = $env['controller'] ?? '';
$action = $env['action'] ?? '';
$params = $env['params'] ?? [];

if ($controller === '' || !class_exists($controller)) {
    \Scriptlog\Core\ApiResponse::notFound('Controller class not found: ' . $controller);
}

$instance = new $controller();

if (!method_exists($instance, $action)) {
    \Scriptlog\Core\ApiResponse::notFound('Action method not found: ' . $action);
}

call_user_func_array([$instance, $action], [$params]);
