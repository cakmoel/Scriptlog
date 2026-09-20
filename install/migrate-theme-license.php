<?php

/**
 * Theme License Migration Script
 *
 * Adds premium theme license columns to tbl_themes and creates the
 * tbl_theme_licenses table for existing installations.
 */

define('SCRIPTLOG', true);

// Universal vendor autoload - works for both standard and Composer installations
if (file_exists(__DIR__ . '/../lib/vendor/autoload.php')) {
    require_once __DIR__ . '/../lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';

echo "Theme License Migration\n";
echo "======================\n\n";

try {
    // Connect to database
    $dsn = 'mysql:host=' . $config['db']['host'] . ';port=' . ($config['db']['port'] ?? '3306') . ';dbname=' . $config['db']['name'];
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $prefix = $config['db']['prefix'] ?? '';
    $themesTable = $prefix . 'tbl_themes';

    // 1. Add columns to tbl_themes (if they do not exist)
    $columns = [
        'theme_type' => "enum('free','premium') NOT NULL DEFAULT 'free'",
        'license_key' => 'VARCHAR(100) DEFAULT NULL',
        'license_status' => "enum('active','inactive','expired','invalid') DEFAULT NULL",
        'license_expires_at' => 'DATETIME DEFAULT NULL',
    ];

    echo "Altering {$themesTable}:\n";

    foreach ($columns as $name => $definition) {
        $colExists = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $colExists->execute([$config['db']['name'], $themesTable, $name]);

        if ($colExists->rowCount() > 0) {
            echo "  - Column '{$name}' already exists, skipping\n";
            continue;
        }

        $pdo->exec("ALTER TABLE {$themesTable} ADD COLUMN `{$name}` {$definition}");
        echo "  - Added column: {$name}\n";
    }

    // Add the license_key secondary index if not present
    $idxExists = $pdo->prepare("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = 'license_key'");
    $idxExists->execute([$config['db']['name'], $themesTable]);

    if ($idxExists->rowCount() === 0) {
        $pdo->exec("ALTER TABLE {$themesTable} ADD INDEX `license_key` (`license_key`)");
        echo "  - Added index: license_key\n";
    } else {
        echo "  - Index 'license_key' already exists, skipping\n";
    }

    // 2. Create tbl_theme_licenses table if it does not exist
    $licensesTable = $prefix . 'tbl_theme_licenses';
    $tableExists = $pdo->query("SHOW TABLES LIKE '{$licensesTable}'")->fetchAll();

    if (count($tableExists) === 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$licensesTable} (
            ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            theme_id INT(11) UNSIGNED NOT NULL,
            license_key VARCHAR(100) NOT NULL,
            license_status VARCHAR(20) NOT NULL DEFAULT 'active',
            domain VARCHAR(255) NOT NULL,
            remote_license_id INT(11) DEFAULT NULL,
            activated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            PRIMARY KEY (ID),
            KEY theme_id (theme_id),
            KEY license_key (license_key),
            KEY domain (domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "  - Created table: {$licensesTable}\n";
    } else {
        echo "  - Table '{$licensesTable}' already exists, checking columns\n";
    }

    // 2b. Normalize an existing table so installations migrated by earlier
    // versions end up identical to the definition the installer now creates.
    $columnsStmt = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    $columnsStmt->execute([$config['db']['name'], $licensesTable]);

    $licensesColumns = [];

    foreach ($columnsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $licensesColumns[$row['COLUMN_NAME']] = $row;
    }

    $expectedColumns = [
        'ID' => ['type' => 'bigint(20) unsigned', 'nullable' => false, 'definition' => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT'],
        'theme_id' => ['type' => 'int(11) unsigned', 'nullable' => false, 'definition' => 'INT(11) UNSIGNED NOT NULL'],
        'domain' => ['type' => 'varchar(255)', 'nullable' => false, 'definition' => 'VARCHAR(255) NOT NULL'],
        'remote_license_id' => ['type' => 'int(11)', 'nullable' => true, 'definition' => 'INT(11) DEFAULT NULL'],
        'activated_at' => ['type' => 'timestamp', 'nullable' => false, 'definition' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
    ];

    if (isset($licensesColumns['domain']) && strcasecmp($licensesColumns['domain']['IS_NULLABLE'], 'YES') === 0) {
        $pdo->exec("UPDATE {$licensesTable} SET domain = '' WHERE domain IS NULL");
    }

    if (isset($licensesColumns['activated_at']) && strcasecmp($licensesColumns['activated_at']['IS_NULLABLE'], 'YES') === 0) {
        $pdo->exec("UPDATE {$licensesTable} SET activated_at = CURRENT_TIMESTAMP WHERE activated_at IS NULL");
    }

    foreach ($expectedColumns as $columnName => $expected) {
        if (!isset($licensesColumns[$columnName])) {
            continue;
        }

        $typeMatches = (strcasecmp($licensesColumns[$columnName]['COLUMN_TYPE'], $expected['type']) === 0);
        $nullMatches = ((strcasecmp($licensesColumns[$columnName]['IS_NULLABLE'], 'YES') === 0) === $expected['nullable']);

        if ($typeMatches && $nullMatches) {
            continue;
        }

        $pdo->exec("ALTER TABLE {$licensesTable} MODIFY COLUMN `{$columnName}` {$expected['definition']}");
        echo "  - Aligned column: {$columnName}\n";
    }

    // Ensure the domain index exists on normalized installations.
    $licensesIndex = $pdo->prepare("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = 'domain'");
    $licensesIndex->execute([$config['db']['name'], $licensesTable]);

    if ($licensesIndex->rowCount() === 0) {
        $pdo->exec("ALTER TABLE {$licensesTable} ADD INDEX `domain` (`domain`)");
        echo "  - Added index: domain\n";
    }

    // Match the utf8mb4_general_ci collation used by the rest of the schema.
    $collationStmt = $pdo->prepare("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    $collationStmt->execute([$config['db']['name'], $licensesTable]);
    $tableCollation = (string) $collationStmt->fetchColumn();

    if ($tableCollation !== '' && strcasecmp($tableCollation, 'utf8mb4_general_ci') !== 0) {
        $pdo->exec("ALTER TABLE {$licensesTable} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo "  - Aligned collation: utf8mb4_general_ci\n";
    }

    // 3. Default empty theme_type rows to 'free'
    $pdo->exec("UPDATE {$themesTable} SET theme_type = 'free' WHERE theme_type IS NULL OR theme_type = ''");
    echo "  - Defaulted empty theme_type rows to 'free'\n";

    // 4. Sync theme_type from each installed theme's theme.ini
    $themesDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'themes';
    $stmtThemes = $pdo->query("SELECT ID, theme_directory FROM {$themesTable}");
    $synced = 0;

    while ($row = $stmtThemes->fetch(PDO::FETCH_ASSOC)) {
        $iniFile = $themesDir . DIRECTORY_SEPARATOR . $row['theme_directory'] . DIRECTORY_SEPARATOR . 'theme.ini';
        if (!file_exists($iniFile)) {
            continue;
        }
        $ini = parse_ini_file($iniFile, true);
        if (!is_array($ini)) {
            continue;
        }
        $iniType = $ini['info']['theme_type'] ?? 'free';
        $dbType  = $row['theme_type'] ?? 'free';

        if ($iniType === 'premium' && $dbType !== 'premium') {
            $pdo->prepare("UPDATE {$themesTable} SET theme_type = 'premium' WHERE ID = ?")
                ->execute([(int)$row['ID']]);
            echo "  - Synced theme_type = premium for: {$row['theme_directory']}\n";
            $synced++;
        }
    }

    if ($synced === 0) {
        echo "  - All theme_type values already match theme.ini manifests\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}