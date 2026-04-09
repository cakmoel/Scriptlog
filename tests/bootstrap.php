<?php
/**
 * Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/lib/common.php';

if (!function_exists('load_core_utilities')) {
    require_once __DIR__ . '/../src/lib/utility-loader.php';
}

// Force load all utilities by calling load_core_utilities
if (function_exists('load_core_utilities')) {
    load_core_utilities();
}

require_once __DIR__ . '/../src/lib/utility/rate-limiter.php';

// Setup autoloader for DAO and Service classes
if (file_exists(__DIR__ . '/../src/lib/Autoloader.php')) {
    require_once __DIR__ . '/../src/lib/Autoloader.php';
    
    if (class_exists('Autoloader')) {
        Autoloader::setBaseDir(__DIR__ . '/..');
        Autoloader::addClassDir(array(
            'src/lib/core'       . DIRECTORY_SEPARATOR,
            'src/lib/dao'        . DIRECTORY_SEPARATOR,
            'src/lib/service'    . DIRECTORY_SEPARATOR,
            'src/lib/controller' . DIRECTORY_SEPARATOR,
            'src/lib/model'      . DIRECTORY_SEPARATOR,
            'src/lib/utility'    . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
