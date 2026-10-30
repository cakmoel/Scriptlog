<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * validate_plugin_schema_sql()
 *
 * Validate a single SQL statement from a plugin's schema.sql.
 * Only allowlisted DDL/DML shapes are permitted; everything else is rejected.
 *
 * @category function
 * @author   Scriptlog Team
 * @license  MIT
 * @version  1.0.0
 * @since    1.10.0
 * @param string $statement SQL statement
 * @param string $pluginSlug Plugin slug for ownership checks
 * @return bool
 */
function validate_plugin_schema_sql($statement, $pluginSlug = '')
{
    if (!is_string($statement)) {
        return false;
    }

    $sql = trim($statement);
    if ($sql === '') {
        return false;
    }

    // Reject multi-statements and comments/inline directives
    if (strpos($sql, ';') !== false) {
        return false;
    }
    if (preg_match('/--|\/\*|\#/', $sql)) {
        return false;
    }

    // Reject dangerous operations
    $upper = strtoupper($sql);
    $forbidden = array('DROP ', 'TRUNCATE ', 'DELETE ', 'UPDATE ', 'GRANT ', 'SET ', 'INTO OUTFILE', 'LOAD_FILE', 'LOAD DATA', 'EXEC ', 'EXECUTE ', 'CALL ', 'RENAME ', 'REVOKE ', 'CREATE USER', 'ALTER USER');
    foreach ($forbidden as $bad) {
        if (strpos($upper, $bad) !== false) {
            return false;
        }
    }

    // Allowlist: CREATE TABLE, CREATE INDEX, ALTER TABLE ADD, INSERT INTO
    $allowed = false;

    // CREATE TABLE [IF NOT EXISTS] table_name
    if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\[]?([a-zA-Z0-9_]+)[`"\]]?/i', $sql, $m)) {
        $table = $m[1];
        if (scriptlog_plugin_schema_table_allowed($table, $pluginSlug)) {
            $allowed = true;
        }
    }

    // CREATE INDEX
    if (!$allowed && preg_match('/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+/i', $sql)) {
        // Must reference an allowed table
        if (preg_match('/ON\s+[`"\[]?([a-zA-Z0-9_]+)[`"\]]?/i', $sql, $m2)) {
            if (scriptlog_plugin_schema_table_allowed($m2[1], $pluginSlug)) {
                $allowed = true;
            }
        } else {
            return false;
        }
    }

    // ALTER TABLE ... ADD [COLUMN|INDEX|CONSTRAINT] ... referencing allowed table
    if (!$allowed && preg_match('/^ALTER\s+TABLE\s+[`"\[]?([a-zA-Z0-9_]+)[`"\]]?/i', $sql, $m3)) {
        $table = $m3[1];
        if (scriptlog_plugin_schema_table_allowed($table, $pluginSlug) && preg_match('/\bADD\b/i', $sql)) {
            $allowed = true;
        }
    }

    // INSERT INTO allowed table
    if (!$allowed && preg_match('/^INSERT\s+INTO\s+[`"\[]?([a-zA-Z0-9_]+)[`"\]]?/i', $sql, $m4)) {
        $table = $m4[1];
        if (scriptlog_plugin_schema_table_allowed($table, $pluginSlug)) {
            $allowed = true;
        }
    }

    return $allowed;
}

/**
 * scriptlog_plugin_schema_table_allowed()
 *
 * @param string $table
 * @param string $pluginSlug
 * @return bool
 */
function scriptlog_plugin_schema_table_allowed($table, $pluginSlug = '')
{
    if (!is_string($table) || $table === '') {
        return false;
    }

    $tableLower = strtolower($table);

    // Allow tables prefixed with tbl_
    if (strpos($tableLower, 'tbl_') !== 0) {
        return false;
    }

    // If plugin slug provided, require table to be owned by plugin
    if (is_string($pluginSlug) && $pluginSlug !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9_]/', '', $pluginSlug));
        if ($slug === '') {
            return false;
        }
        // Allow tbl_{slug}_* or tables that include slug
        if (strpos($tableLower, 'tbl_' . $slug . '_') === 0 || strpos($tableLower, '_' . $slug . '_') !== false) {
            return true;
        }
        // Also allow if slug appears after tbl_
        if (strpos($tableLower, 'tbl_' . $slug) === 0) {
            return true;
        }
        return false;
    }

    // Without slug, only allow very restricted tables - be conservative
    return false;
}
