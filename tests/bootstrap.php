<?php
/**
 * Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/lib/vendor/autoload.php';
require_once __DIR__ . '/../src/lib/common.php';

if (!function_exists('load_core_utilities')) {
    require_once __DIR__ . '/../src/lib/utility-loader.php';
}

// Force load all utilities by calling load_core_utilities
if (function_exists('load_core_utilities')) {
    load_core_utilities();
}

require_once __DIR__ . '/../src/lib/utility/rate-limiter.php';
require_once __DIR__ . '/../src/lib/core/ApiHateoas.php';

// Register lazy PSR-4 backward-compatibility aliases FIRST
// so old class names resolve before the legacy Autoloader tries to include files.
if (file_exists(__DIR__ . '/../src/lib/autoload-aliases-map.php')) {
    $scriptlogAliasMap = require __DIR__ . '/../src/lib/autoload-aliases-map.php';
    spl_autoload_register(function ($className) use ($scriptlogAliasMap) {
        if (isset($scriptlogAliasMap[$className])) {
            class_alias($scriptlogAliasMap[$className], $className);
        }
    });
}

// Setup autoloader for legacy class directories (files without namespaces)
// Namespaced classes under src/lib/ are handled by Composer's PSR-4 autoloader.
if (file_exists(__DIR__ . '/../src/lib/Autoloader.php')) {
    require_once __DIR__ . '/../src/lib/Autoloader.php';
    
    if (class_exists('Autoloader')) {
        Autoloader::setBaseDir(__DIR__ . '/..');
        Autoloader::addClassDir(array(
            // Only directories with non-namespaced files or procedural utilities
            'src/lib/utility'    . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['PHP_SELF'] = '/index.php';
