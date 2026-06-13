<?php
/**
 * Test Bootstrap for Installation Tests
 * 
 * Separate bootstrap to avoid function redeclaration conflicts
 * with db-mysqli.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Don't load the normal bootstrap - load minimal required files
require_once __DIR__ . '/../lib/vendor/autoload.php';

// Load common.php but avoid loading utility-loader.php
// which would load db-mysqli.php (conflicting function definitions)
require_once __DIR__ . '/../lib/common.php';

// Manually load only the installation files
require_once __DIR__ . '/../install/include/check-engine.php';
require_once __DIR__ . '/../install/include/setup.php';

// Set up mock $_SERVER values
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
