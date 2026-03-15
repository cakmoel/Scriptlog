<?php
/**
 * Integration Test Bootstrap
 * 
 * Bootstrap file for Scriptlog Integration Tests
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../lib/vendor/autoload.php';
require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/../lib/utility-loader.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';

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
            $db = new Db($config['db']);
            if (method_exists($db, 'setConnection')) {
                $db->setConnection($pdo);
            }
            Registry::set('db', $db);
        }
        
    } catch (PDOException $e) {
        echo "Failed to connect to test database: " . $e->getMessage() . "\n";
    }
}

set_test_database_connection();
