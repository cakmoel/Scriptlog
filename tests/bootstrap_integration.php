<?php
/**
 * Integration Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Integration Tests with database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/lib/common.php';

// Load only essential utilities for testing
$essential_utilities = [
    'access-control-list.php',
    'admin-query.php',
    'app-config.php',
    'app-info.php',
    'app-reading-setting.php',
    'build-query.php',
    'check-pwd-strength.php',
    'form-id.php',
    'generate-token.php',
    'media-properties.php',
    'mime-type-dictionary.php',
    'random-generator.php',
    'user-info.php',
    'user-privilege.php',
    'worst-passwords.php',
];

$utility_dir = __DIR__ . '/../src/lib/utility/';
foreach ($essential_utilities as $file) {
    if (file_exists($utility_dir . $file)) {
        require_once $utility_dir . $file;
    }
}

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
            'src/lib/model'      . DIRECTORY_SEPARATOR
        ));
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
$_SERVER['SERVER_NAME'] = 'localhost';

function get_test_config(): array 
{
    return [
        'db' => [
            'host' => 'localhost',
            'user' => 'blogwareuser',
            'pass' => 'userblogware',
            'name' => 'blogware_test',
            'port' => '3306'
        ],
        'app' => [
            'url'   => 'http://blogware.site',
            'email' => 'admin@test.com',
            'key'   => 'GVXUD7-72HUXD-2TFCDT-8DDC2A'
        ],
    ];
}

function set_test_database_connection(): void
{
    $config = get_test_config();
    
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['name']
    );
    
    try {
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        if (class_exists('Registry')) {
            Registry::set('dbc', $pdo);
        }
        
        if (class_exists('Db')) {
            $dbConfig = [
                $dsn,
                $config['db']['user'],
                $config['db']['pass']
            ];
            $db = new Db($dbConfig);
            Registry::set('db', $db);
        }
        
    } catch (PDOException $e) {
        echo "Failed to connect to test database: " . $e->getMessage() . "\n";
    }
}

// Prevent TablePrefixTest.php from loading
define('TEST_LOADING', true);
