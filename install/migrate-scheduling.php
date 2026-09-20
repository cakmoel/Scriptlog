<?php

/**
 * Scheduled Posting Migration Script
 *
 * Adds the composite index idx_post_status_date on tbl_posts (post_status, post_date)
 * and seeds the writing_scheduled_post_enabled setting for existing installations.
 */

define('SCRIPTLOG', true);

// Universal vendor autoload - works for both standard and Composer installations
if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';

echo "Scheduled Posting Migration\n";
echo "==========================\n\n";

try {
    // Connect to database
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';
    $postsTable = $prefix . 'tbl_posts';
    $settingsTable = $prefix . 'tbl_settings';

    // 1. Add the idx_post_status_date index if it does not exist
    $indexExists = $pdo->query("SHOW INDEX FROM {$postsTable} WHERE Key_name = 'idx_post_status_date'")->fetch();

    if (false === $indexExists) {
        $pdo->exec("ALTER TABLE {$postsTable} ADD INDEX idx_post_status_date (post_status, post_date)");
        echo "  - Created index: idx_post_status_date on {$postsTable}\n";
    } else {
        echo "  - Index already exists: idx_post_status_date on {$postsTable}\n";
    }

    // 2. Seed the scheduled posting toggle (enabled by default)
    $check = $pdo->prepare("SELECT ID FROM {$settingsTable} WHERE setting_name = ?");
    $check->execute(['writing_scheduled_post_enabled']);

    if ($check->rowCount() > 0) {
        echo "  - Setting already exists: writing_scheduled_post_enabled\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO {$settingsTable} (setting_name, setting_value) VALUES (?, ?)");
        $stmt->execute(['writing_scheduled_post_enabled', '1']);
        echo "  - Created setting: writing_scheduled_post_enabled = '1'\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
