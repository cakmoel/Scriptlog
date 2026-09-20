<?php
/**
 * Router / front controller for the Playwright e2e test server.
 *
 * The PHP built-in development server invokes this script for every request.
 * It forces the environment variables that config.php reads (see config.php)
 * so the e2e browser tests run against the dedicated blogware_e2e database
 * without touching the gitignored config.php or the live production DB.
 *
 * It also emulates the .htaccess / nginx-rewrites front-controller behaviour:
 * requests that do not map to a real static file are forwarded to index.php so
 * the app's Dispatcher owns 404 handling (it never happens in templates).
 * Requests for existing files are passed through unchanged (return false).
 *
 * @category E2E Fixture
 * @package  Scriptlog
 * @license  MIT
 */

$_ENV['DB_NAME'] = 'blogware_e2e';
$_ENV['DB_USER'] = 'blogwareuser';
$_ENV['DB_PASS'] = 'userblogware';
$_ENV['DB_HOST'] = '127.0.0.1';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_PREFIX'] = '';
$_ENV['APP_KEY'] = 'GVXUD7-72HUXD-2TFCDT-8DDC2A';
$_ENV['APP_URL'] = 'http://127.0.0.1:8099';

// The production deployment is served over HTTPS (Apache on :443). Mark the
// request as secure so the app skips the `upgrade-insecure-requests` CSP
// directive: without this, WebKit rewrites every http:// stylesheet to
// https://127.0.0.1:8099, which this HTTP-only dev server cannot serve, so
// Bootstrap/theme CSS never loads and responsive layout tests fail.
//
// The spoof is limited to HTML document navigations on purpose: is_ssl() also
// drives the session cookie's Secure flag (is_cookies_secured()), and WebKit
// refuses to store Secure cookies delivered over plain HTTP - which broke the
// unlock-persistence flow (plan finding R7) because the _scriptlog cookie set
// by the unlock XHR never survived. Non-document requests (API/XHR/fetch) see
// HTTPS off, so their cookies stay storable while document CSP stays correct.
$routerAccept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
$routerIsDocument =
    (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === 'GET')
    && strpos($routerAccept, 'text/html') !== false;
if ($routerIsDocument) {
    $_SERVER['HTTPS'] = 'on';
}

// The app derives APP_PROTOCOL from $_SERVER['HTTPS'], which would make every
// admin redirect (direct_page/login) target https://127.0.0.1:8099 and break
// against this HTTP-only server. Pin the protocol to http up front (common.php
// only defines APP_PROTOCOL when it is not already defined).
if (!defined('APP_PROTOCOL')) {
    define('APP_PROTOCOL', 'http');
}

// Dispatch the request. Files ending in .php are required here, in process,
// so the environment forced above (and APP_PROTOCOL) stays visible on every
// PHP built-in-server version. Static assets are passed through unchanged.
$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$docRoot = __DIR__ . '/..';
$staticFile = realpath($docRoot . '/' . ltrim($requestedPath, '/'));

if (is_file($staticFile)) {
    if (stripos($staticFile, '.php') === strlen($staticFile) - 4) {
        require $staticFile;
        return;
    }
    return false;
}

// Mirror .htaccess: /api/* is handled by its own front controller.
if (strpos($requestedPath, '/api') === 0) {
    require __DIR__ . '/../api/index.php';
    return;
}

// Otherwise route through the front controller (index.php).
require __DIR__ . '/../index.php';
