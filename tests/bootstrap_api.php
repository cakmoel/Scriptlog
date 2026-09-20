<?php
/**
 * API Test Bootstrap
 *
 * Bootstrap for the Phase 4.7 API test suite (tests/api/).
 *
 * Loads the full application environment (utilities, PSR-4 aliases, rate
 * limiter, HATEOAS) and sets up the test database connection so both
 * controller unit tests and database-backed integration tests can run.
 *
 * The global phpunit.xml bootstrap (tests/bootstrap.php) is the default for
 * the test run; this file is used when executing the API suite directly:
 *
 *     lib/vendor/bin/phpunit --bootstrap tests/bootstrap_api.php tests/api
 *
 * @package Scriptlog\Tests
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../lib/vendor/autoload.php';
require_once __DIR__ . '/../lib/common.php';

// Load installation functions before the utility loader so that
// is_table_exists() is defined by install/include/setup.php first.
if (!function_exists('current_url')) {
    require_once __DIR__ . '/../install/include/setup.php';
}

if (!function_exists('load_core_utilities')) {
    require_once __DIR__ . '/../lib/utility-loader.php';
}

// Force-load all utilities.
if (function_exists('load_core_utilities')) {
    load_core_utilities();
}

// Force-load the rate limiter and API HATEOAS helpers.
require_once __DIR__ . '/../lib/utility/rate-limiter.php';
require_once __DIR__ . '/../lib/core/ApiHateoas.php';

// PSR-4 backward-compatibility aliases.
if (file_exists(__DIR__ . '/../lib/autoload-aliases-map.php')) {
    $scriptlogAliasMap = require __DIR__ . '/../lib/autoload-aliases-map.php';
    spl_autoload_register(function ($className) use ($scriptlogAliasMap) {
        if (isset($scriptlogAliasMap[$className])) {
            class_alias($scriptlogAliasMap[$className], $className);
        }
    });
}

// Server environment.
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
$_SERVER['SERVER_NAME'] = 'localhost';

// Database connection for database-backed API tests.
require_once __DIR__ . '/bootstrap_integration.php';
set_test_database_connection();

define('TEST_API', true);
