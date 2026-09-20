<?php
/**
 * Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Flag test mode: get_table_prefix() and whoops_error() (and other
// environment-dependent bootstrapping) skip production behavior so the
// PHPUnit process stays deterministic.
define('SCRIPTLOG_TEST_MODE', true);

// DAO base classes resolve the table prefix via get_table_prefix(), which
// reads config.php (default 'tpglkl_'). The test database uses unprefixed
// table names, so flag test mode to force an empty prefix.
$GLOBALS['__test_prefix'] = '';

require_once __DIR__ . '/../lib/vendor/autoload.php';
require_once __DIR__ . '/../lib/common.php';

// Load installation functions before utility-loader to define
// is_table_exists() first (setup.php version takes priority)
if (!function_exists('current_url')) {
    require_once __DIR__ . '/../install/include/setup.php';
}

if (!function_exists('load_core_utilities')) {
    require_once __DIR__ . '/../lib/utility-loader.php';
}

// Force load all utilities by calling load_core_utilities
if (function_exists('load_core_utilities')) {
    load_core_utilities();
}

require_once __DIR__ . '/../lib/utility/rate-limiter.php';
require_once __DIR__ . '/../lib/core/ApiHateoas.php';

// Lazy PSR-4 backward-compatibility aliases.
// MUST be registered BEFORE the custom Autoloader (mirrors lib/main.php):
// when a legacy class name (e.g. 'Registry') is referenced via class_exists()
// but its namespaced counterpart is already loaded, resolving it through
// Autoloader::loadClass would re-include the class file and fatal with
// "Cannot redeclare class".
if (file_exists(__DIR__ . '/../lib/autoload-aliases-map.php')) {
    $scriptlogAliasMap = require __DIR__ . '/../lib/autoload-aliases-map.php';
    spl_autoload_register(function ($className) use ($scriptlogAliasMap) {
        if (isset($scriptlogAliasMap[$className])) {
            class_alias($scriptlogAliasMap[$className], $className);
        }
    });
}

// Setup autoloader for DAO and Service classes
if (file_exists(__DIR__ . '/../lib/Autoloader.php')) {
    require_once __DIR__ . '/../lib/Autoloader.php';
    
    if (class_exists('Autoloader')) {
        Autoloader::setBaseDir(__DIR__ . '/..');
        Autoloader::addClassDir(array(
            'src/lib/core'       . DIRECTORY_SEPARATOR,
            'src/lib/dao'        . DIRECTORY_SEPARATOR,
            'src/lib/service'    . DIRECTORY_SEPARATOR,
            'src/lib/controller' . DIRECTORY_SEPARATOR,
            'src/lib/model'      . DIRECTORY_SEPARATOR,
            'src/lib/utility'    . DIRECTORY_SEPARATOR,
            'src/lib/handler'    . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
