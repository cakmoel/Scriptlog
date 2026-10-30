<?php

// This script runs DDL with the full database credentials. Web
// execution is unauthenticated by design (no bootstrap, no login), so refuse
// everything except CLI. Upgrades run `php install/migrate-*.php` over SSH/cron.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}


/**
 * Plugin Manifest Migration
 *
 * Adds the plugin integrity column used by the Phase 2 RCE remediation
 * (plan/RCE_REMEDIATION_OCTOBER2026_PLAN.md, tasks 2.7-2.8):
 *
 *   - plugin_manifest CHAR(64) DEFAULT NULL
 *
 * The column stores the sha256 digest of `plugin.ini` plus every PHP file in
 * the plugin directory, computed at install time and re-verified before
 * activation. Rows created before this column exists get NULL, which
 * plugin_manifest_verify() treats as "unverifiable" and therefore refuses to
 * activate.
 *
 * Usage:
 *   php install/migrate-plugin-manifest.php             # add the column only
 *   php install/migrate-plugin-manifest.php --backfill  # also record digests for
 *                                                       # installed plugins
 *
 * `--backfill` is deliberately explicit: it trusts whatever is currently on
 * disk. Only run it after confirming the plugin directories are the files you
 * intend to run. Newly uploaded plugins always record their digest
 * automatically and never need a backfill.
 *
 * Safe to run twice: the column is added only when INFORMATION_SCHEMA proves
 * it is missing, and rows that already carry a digest are left untouched.
 * Portable: plain PDO + ALTER TABLE, no extensions.
 */

define('SCRIPTLOG', true);

if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../lib/utility/plugin-manifest.php';

$config = require __DIR__ . '/../config.php';

$backfill = in_array('--backfill', array_slice($argv, 1), true);

echo "Plugin Manifest Migration\n";
echo "=========================\n\n";

try {
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';
    $table = $prefix . 'tbl_plugin';

    $check = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
        . "WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'plugin_manifest'"
    );
    $check->execute(array($config['db']['name'], $table));
    $exists = $check->fetch();

    if (false === $exists) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN plugin_manifest CHAR(64) DEFAULT NULL");
        echo "  - Added column: plugin_manifest on {$table}\n";
    } else {
        echo "  - Column already exists: plugin_manifest on {$table}\n";
    }

    if ($backfill) {

        echo "\nBackfilling plugin manifests from the current plugin directories:\n";

        $pluginRoot = __DIR__ . '/../admin/plugins/';

        $select = $pdo->prepare(
            "SELECT ID, plugin_name, plugin_directory FROM {$table} "
            . "WHERE plugin_manifest IS NULL OR plugin_manifest = ''"
        );
        $select->execute();
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "  - Nothing to backfill\n";
        }

        $update = $pdo->prepare("UPDATE {$table} SET plugin_manifest = ? WHERE ID = ?");

        foreach ($rows as $row) {

            $directory = basename((string)$row['plugin_directory']);

            if ($directory === '' || $directory === '.' || $directory === '..') {
                echo "  ! Skipped ID {$row['ID']} ({$row['plugin_name']}): invalid plugin_directory\n";
                continue;
            }

            $digest = plugin_manifest_hash($pluginRoot . $directory);

            if ($digest === false) {
                echo "  ! Skipped ID {$row['ID']} ({$row['plugin_name']}): plugin.ini missing or unreadable\n";
                continue;
            }

            $update->execute(array($digest, (int)$row['ID']));
            echo "  - Recorded manifest for ID {$row['ID']} ({$row['plugin_name']}): {$digest}\n";

        }

    } else {

        $pending = $pdo->query(
            "SELECT COUNT(ID) FROM {$table} WHERE plugin_manifest IS NULL OR plugin_manifest = ''"
        )->fetchColumn();

        if ((int)$pending > 0) {
            echo "\n  ! " . (int)$pending . " installed plugin(s) still have no manifest and "
                . "cannot be activated until re-installed or backfilled with --backfill.\n";
        }

    }

    echo "\nMigration completed successfully!\n";
    exit(0);
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
