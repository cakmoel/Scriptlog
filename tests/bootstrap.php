<?php
/**
 * Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../lib/vendor/autoload.php';
require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/../lib/utility-loader.php';

// Setup autoloader for DAO and Service classes
if (file_exists(__DIR__ . '/../lib/Autoloader.php')) {
    require_once __DIR__ . '/../lib/Autoloader.php';
    
    if (class_exists('Autoloader')) {
        Autoloader::setBaseDir(__DIR__ . '/..');
        Autoloader::addClassDir(array(
            'lib/core'       . DIRECTORY_SEPARATOR,
            'lib/dao'        . DIRECTORY_SEPARATOR,
            'lib/service'    . DIRECTORY_SEPARATOR,
            'lib/controller' . DIRECTORY_SEPARATOR,
            'lib/model'      . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
