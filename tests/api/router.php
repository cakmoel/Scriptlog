<?php
/**
 * PHP Built-in Server Router (tests only)
 *
 * Router script passed to `php -S` for the API integration tests. Routes
 * every /api/v1/* request to api/index.php (which handles routing itself)
 * and lets everything else fall through to the static-file handler so the
 * local test server behaves like the production web server.
 *
 * Usage:
 *   php -S 127.0.0.1:PORT -t <project-root> tests/api/router.php
 *
 * @package Scriptlog\Tests
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($path, '/api/v1') === 0) {
    require __DIR__ . '/../../api/index.php';
    return true;
}

return false;
