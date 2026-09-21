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
$_ENV['DB_HOST'] = 'localhost';
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

// The HTTPS spoof above also makes the app mark its session/auth cookies
// "Secure" (is_cookies_secured() mirrors is_ssl()). The PHP dev server is plain
// HTTP, and WebKit refuses to store Secure cookies received over an insecure
// connection - so the session the admin login created evaporates on the very
// next document navigation and the flow bounces back to the login page. Keep
// the spoof (it silences the CSP upgrade-insecure-requests directive and lets
// Bootstrap/theme CSS render) but scrub the Secure flag off every Set-Cookie
// header before the response is flushed.
// Hold ALL output in RAM so the header rewrite below can always run. The
// built-in server default (output_buffering=0) flushes headers with the page's
// first byte, and a plain ini_set() only raises the auto-flush threshold - a
// large page (the dashboard) still flushes mid-request and the Secure-flag
// scrub below silently no-ops. A chunked ob_start(NULL, 0) never auto-flushes.
ini_set('zlib.output_compression', 'Off');
ob_start(null, 0);
register_shutdown_function(function (): void {
    // Never let the header rewrite throw: a request may have already flushed
    // its headers (the app decides to send the response early), and PHP turns
    // header_remove()/header() warnings into an ErrorException caught by the
    // app's Whoops handler, which would replace a valid download with an error
    // page. When headers already went out the Secure-flag scrub is a no-op.
    if (headers_sent()) {
        return;
    }
    $cleanCookies = [];
    foreach (headers_list() as $header) {
        if (stripos($header, 'Set-Cookie:') === 0) {
            $cleanCookies[] = preg_replace('/;\s*Secure\b/i', '', $header);
        }
    }
    if ($cleanCookies) {
        @header_remove('Set-Cookie');
        foreach ($cleanCookies as $cookie) {
            @header($cookie, false);
        }
    }
});

// The app derives APP_PROTOCOL from $_SERVER['HTTPS'], which would make every
// admin redirect (direct_page/login) target https://127.0.0.1:8099 and break
// against this HTTP-only server. Pin the protocol to http up front (common.php
// only defines APP_PROTOCOL when it is not already defined).
if (!defined('APP_PROTOCOL')) {
    define('APP_PROTOCOL', 'http');
}

// If the requested URI maps to an existing file, serve it statically.
$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$docRoot = __DIR__ . '/..';
$staticFile = realpath($docRoot . '/' . ltrim($requestedPath, '/'));

if (is_file($staticFile)) {
    return false;
}

// Mirror .htaccess: /api/* is handled by its own front controller.
if (strpos($requestedPath, '/api') === 0) {
    require __DIR__ . '/../api/index.php';
    return;
}

// Otherwise route through the front controller (index.php).
require __DIR__ . '/../index.php';
