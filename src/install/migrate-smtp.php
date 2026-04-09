<?php

/**
 * SMTP Migration Script
 *
 * This script moves existing SMTP settings from config.php to tbl_settings.
 */

define('SCRIPTLOG', true);

// Universal vendor autoload - works for both standard and Composer installations
if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';

echo "SMTP Configuration Migration\n";
echo "===========================\n\n";

try {
    // Connect to database
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';
    $smtp = $config['mail']['smtp'] ?? [];
    $from = $config['mail']['from'] ?? [];

    $settings = [
        'smtp_host' => $smtp['host'] ?? '',
        'smtp_port' => $smtp['port'] ?? '587',
        'smtp_encryption' => $smtp['encryption'] ?? 'tls',
        'smtp_username' => $smtp['username'] ?? '',
        'smtp_password' => $smtp['password'] ?? '',
        'smtp_from_email' => $from['email'] ?? '',
        'smtp_from_name' => $from['name'] ?? 'Blogware'
    ];

    echo "Migrating settings to {$prefix}tbl_settings:\n";

    foreach ($settings as $name => $value) {
        // Check if setting already exists
        $check = $pdo->prepare("SELECT ID FROM {$prefix}tbl_settings WHERE setting_name = ?");
        $check->execute([$name]);

        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE {$prefix}tbl_settings SET setting_value = ? WHERE setting_name = ?");
            $stmt->execute([$value, $name]);
            echo "  - Updated: {$name}\n";
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) VALUES (?, ?)");
            $stmt->execute([$name, $value]);
            echo "  - Created: {$name}\n";
        }
    }

    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
