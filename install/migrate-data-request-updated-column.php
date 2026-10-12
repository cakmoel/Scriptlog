<?php

// This script runs DDL with the full database credentials. Web
// execution is unauthenticated by design (no bootstrap, no login), so refuse
// everything except CLI. Upgrades run `php install/migrate-*.php` over SSH/cron.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}


/**
 * Data-Request Updated-Column Migration
 *
 * Adds the missing `request_updated` column to tbl_data_requests on installs
 * whose tables predate the column in install/include/dbtable.php:
 *
 *   - request_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
 *
 * Safe to run twice: the column is added only when INFORMATION_SCHEMA proves
 * it is missing. Portable: plain PDO + ALTER TABLE, no extensions.
 */

define('SCRIPTLOG', true);

if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';

echo "Data-Request Updated-Column Migration\n";
echo "=====================================\n\n";

try {
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';
    $table = $prefix . 'tbl_data_requests';

    $check = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
        . "WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'request_updated'"
    );
    $check->execute(array($config['db']['name'], $table));
    $exists = $check->fetch();

    if (false === $exists) {
        $pdo->exec(
            "ALTER TABLE {$table} ADD COLUMN request_updated TIMESTAMP NULL DEFAULT NULL "
            . "ON UPDATE CURRENT_TIMESTAMP"
        );
        echo "  - Added column: request_updated on {$table}\n";
    } else {
        echo "  - Column already exists: request_updated on {$table}\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
