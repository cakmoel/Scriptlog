<?php

// C2: this script runs DDL with the full database credentials. Web
// execution is unauthenticated by design (no bootstrap, no login), so refuse
// everything except CLI. Upgrades run `php install/migrate-*.php` over SSH/cron.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}


/**
 * Hot-Filter Index Migration (S10)
 *
 * Adds the missing indexes for the hot frontend filters identified in
 * plan/PERFORMANCE_PLAN_SEPTEMBER2026.md section S10:
 *
 *   - idx_comment_post_status on tbl_comments(comment_post_id, comment_status)
 *   - idx_post_date on tbl_posts(post_date)
 *   - idx_post_topic_topic on tbl_post_topic(topic_id)
 *   - idx_post_type_status on tbl_posts(post_type, post_status, post_date)
 *
 * Safe to run twice: each index is created only when SHOW INDEX proves it
 * is missing. Portable: plain PDO + ALTER TABLE, no extensions.
 */

define('SCRIPTLOG', true);

if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';

echo "Hot-Filter Index Migration (S10)\n";
echo "================================\n\n";

try {
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';

    $indexes = array(
        array(
            'table' => $prefix . 'tbl_comments',
            'name' => 'idx_comment_post_status',
            'ddl' => 'ADD INDEX idx_comment_post_status (comment_post_id, comment_status)',
        ),
        array(
            'table' => $prefix . 'tbl_posts',
            'name' => 'idx_post_date',
            'ddl' => 'ADD INDEX idx_post_date (post_date)',
        ),
        array(
            'table' => $prefix . 'tbl_post_topic',
            'name' => 'idx_post_topic_topic',
            'ddl' => 'ADD INDEX idx_post_topic_topic (topic_id)',
        ),
        array(
            'table' => $prefix . 'tbl_posts',
            'name' => 'idx_post_type_status',
            'ddl' => 'ADD INDEX idx_post_type_status (post_type, post_status, post_date)',
        ),
    );

    foreach ($indexes as $index) {
        $check = $pdo->prepare("SHOW INDEX FROM {$index['table']} WHERE Key_name = ?");
        $check->execute(array($index['name']));
        $exists = $check->fetch();

        if (false === $exists) {
            $pdo->exec("ALTER TABLE {$index['table']} " . $index['ddl']);
            echo "  - Created index: {$index['name']} on {$index['table']}\n";
        } else {
            echo "  - Index already exists: {$index['name']} on {$index['table']}\n";
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
