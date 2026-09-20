<?php

/**
 * API Keys Table Migration
 *
 * Creates the dedicated tbl_api_keys table for secure API key storage.
 * Uses the configured table prefix from config.php.
 *
 * Usage:
 *   php install/migrate-api-keys.php
 */

$config = require __DIR__ . '/../config.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['db']['host'],
    $config['db']['port'] ?? 3306,
    $config['db']['name']
);

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $prefix = $config['db']['prefix'] ?? '';
    $tableName = $prefix . 'tbl_api_keys';

    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
        `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `key_hash` VARCHAR(255) NOT NULL COMMENT 'password_hash() of the raw API key',
        `description` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `expires_at` DATETIME DEFAULT NULL,
        `last_used_at` DATETIME DEFAULT NULL,
        `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_key_hash` (`key_hash`(191)),
        CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}tbl_users` (`ID`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);

    echo "Created table: {$tableName}\n";

    // Remove legacy plaintext keys from tbl_settings
    $settingsTable = $prefix . 'tbl_settings';
    $stmt = $pdo->prepare("DELETE FROM `{$settingsTable}` WHERE setting_name LIKE 'api_key_user_%'");
    $stmt->execute();
    echo "Cleaned up " . $stmt->rowCount() . " legacy key(s) from {$settingsTable}\n";

    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
