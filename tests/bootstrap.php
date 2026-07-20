<?php
/**
 * Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('HTMLPURIFIER_PREFIX')) {
    define('HTMLPURIFIER_PREFIX', __DIR__ . '/../src/lib/core');
}

date_default_timezone_set('UTC');

require_once __DIR__ . '/../src/lib/vendor/autoload.php';

// Register lazy PSR-4 backward-compatibility aliases BEFORE loading utilities
// so class_exists('Dao'), class_exists('Sanitize'), etc. resolve via alias autoloader.
if (file_exists(__DIR__ . '/../src/lib/autoload-aliases-map.php')) {
    $scriptlogAliasMap = require __DIR__ . '/../src/lib/autoload-aliases-map.php';
    spl_autoload_register(function ($className) use ($scriptlogAliasMap) {
        if (isset($scriptlogAliasMap[$className])) {
            class_alias($scriptlogAliasMap[$className], $className);
        }
    });
}

require_once __DIR__ . '/../src/lib/common.php';

// Load all core utilities from the generated loader
if (!function_exists('load_core_utilities')) {
    require_once __DIR__ . '/../src/lib/utility-loader.php';
}

// Register a mock DB object in Registry so app_info() / date_for_database() work without MySQL.
if (class_exists('\\Scriptlog\\Core\\Registry')) {
    $testDb = new class() {
        public function select() { return []; }
        public function get() { return null; }
        public function dbSelect() { return []; }
        public function dbInsert($table, $params) { return true; }
        public function dbUpdate($table, $params, $where) { return 1; }
        public function dbDelete($table, $where, $limit = null) { return 1; }
        public function dbQuery($sql, $args = []) { return new \PDOStatement(); }
        public function dbLastInsertId() { return '1'; }
        public function dbTransaction() { return true; }
        public function dbCommit() { return true; }
        public function dbRollBack() { return true; }
        public function dbReplace($table, $params, $updateParams) { return true; }
    };
    \Scriptlog\Core\Registry::set('dbc', $testDb);
}

require_once __DIR__ . '/../src/lib/utility/rate-limiter.php';
require_once __DIR__ . '/../src/lib/core/ApiHateoas.php';

// Setup autoloader for legacy non-namespaced utility files
if (file_exists(__DIR__ . '/../src/lib/Autoloader.php')) {
    require_once __DIR__ . '/../src/lib/Autoloader.php';
    
    if (class_exists('Autoloader')) {
        Autoloader::setBaseDir(__DIR__ . '/..');
        Autoloader::addClassDir(array(
            'src/lib/utility'    . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['PHP_SELF'] = '/index.php';
