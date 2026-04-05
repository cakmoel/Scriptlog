<?php

/**
 * medoo_init
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
function medoo_init()
{

    // Use existing connection from Registry if available (includes table prefix)
    if (class_exists('Registry')) {
        $dbc = Registry::get('dbc');
        if (is_object($dbc)) {
            return $dbc;
        }
    }

    // Fallback: create new Medoo connection (for backward compatibility)
    $read_config = class_exists('AppConfig') ? AppConfig::readConfiguration(invoke_config()) : null;

    $configuration = [
      'type'     => 'mysql',
      'host'     => isset($read_config['db']['host']) ? $read_config['db']['host'] : "",
      'database' => isset($read_config['db']['name']) ? $read_config['db']['name'] : "",
      'username' => isset($read_config['db']['user']) ? $read_config['db']['user'] : "",
      'password' => isset($read_config['db']['pass']) ? $read_config['db']['pass'] : "",
      'charset'  => 'utf8mb4',
      'collation' => 'utf8mb4_general_ci',
      'port'     => isset($read_config['db']['port']) ? $read_config['db']['port'] : ""
    ];

    return (class_exists('MedooInit')) ? MedooInit::connect($configuration) : "";
}

/**
 * is_medoo_database
 * Check if database object is Db (has Db methods)
 * Returns true for both Db class and Medoo class
 */
function is_medoo_database($database)
{
    if (!is_object($database)) {
        return false;
    }
    // Check for Db class (PDO wrapper with table prefix support)
    if (method_exists($database, 'dbSelect')) {
        return true;
    }
    // Check for Medoo class (has Medoo methods)
    return method_exists($database, 'select');
}

/**
 * is_db_database
 * Check if database object is Db class
 */
function is_db_database($database)
{
    return is_object($database) && method_exists($database, 'dbSelect');
}

/**
 * Build WHERE clause from Medoo-style where array
 *
 * @param array $where Medoo-style where conditions
 * @return array ['sql' => string, 'params' => array]
 */
function db_build_where($where)
{
    if (empty($where)) {
        return ['sql' => '', 'params' => []];
    }

    $conditions = [];
    $params = [];

    foreach ($where as $key => $value) {
        if (is_array($value)) {
            // Handle IN clause: ['column' => ['value1', 'value2']]
            $placeholders = implode(', ', array_fill(0, count($value), '?'));
            $conditions[] = "`{$key}` IN ({$placeholders})";
            $params = array_merge($params, array_values($value));
        } elseif (strpos($key, '>') !== false || strpos($key, '<') !== false || strpos($key, '!') !== false) {
            // Handle operators: 'column>' => value, 'column<' => value, 'column!' => value
            $op = '';
            if (strpos($key, '>=') !== false) {
                $col = str_replace('>=', '', $key);
                $op = '>=';
            } elseif (strpos($key, '<=') !== false) {
                $col = str_replace('<=', '', $key);
                $op = '<=';
            } elseif (strpos($key, '!=') !== false) {
                $col = str_replace('!=', '', $key);
                $op = '!=';
            } elseif (strpos($key, '>') !== false) {
                $col = str_replace('>', '', $key);
                $op = '>';
            } elseif (strpos($key, '<') !== false) {
                $col = str_replace('<', '', $key);
                $op = '<';
            }
            $conditions[] = "`" . trim($col) . "` {$op} ?";
            $params[] = $value;
        } elseif ($key === 'OR') {
            // Handle OR conditions
            $orConditions = [];
            foreach ($value as $orKey => $orValue) {
                $orConditions[] = "`{$orKey}` = ?";
                $params[] = $orValue;
            }
            $conditions[] = '(' . implode(' OR ', $orConditions) . ')';
        } else {
            $conditions[] = "`{$key}` = ?";
            $params[] = $value;
        }
    }

    $sql = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
    return ['sql' => $sql, 'params' => $params];
}

/**
 * medoo_column()
 *
 * select data from the table
 *
 * @category function
 * @param string $table
 * @param string|array $columns
 * @return array|mixed
 *
 */
function medoo_column($table, $columns)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class (has dbSelect), build SQL query
        if (is_db_database($database)) {
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $sql = "SELECT {$cols} FROM {$table}";
            // Use FETCH_ASSOC to return associative array (matches Medoo behavior)
            return $database->dbSelect($sql, [], PDO::FETCH_ASSOC);
        }
        // Medoo class
        return $database->select($table, $columns);
    }
    return [];
}

/**
 * medoo_column_where()
 *
 * @category function
 * @param string $table
 * @param string|array $columns
 * @param array $where
 * @return mixed|array
 *
 */
function medoo_column_where($table, $columns, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class (has dbSelect), build SQL query
        if (is_db_database($database)) {
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $whereClause = db_build_where($where);
            $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];
            // Use FETCH_ASSOC to return associative array (matches Medoo behavior)
            return $database->dbSelect($sql, $whereClause['params'], PDO::FETCH_ASSOC);
        }
        // Medoo class
        return $database->select($table, $columns, $where);
    }
    return [];
}

/**
 * medoo_join
 *
 * @param string $table
 * @param array $join
 * @param array|string $columns
 * @param array $where
 *
 */
function medoo_join($table, $join, $columns, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, we need to build SQL manually for JOINs
        if (is_db_database($database)) {
            // Build JOIN clause from join array
            // Format: ['>' => 'table2.table_id:tbl_posts.post_id'] or ['JOIN_TYPE' => 'table2 ON table2.id = table1.id']
            $joinClauses = [];
            foreach ($join as $type => $condition) {
                $joinType = trim($type);
                if (strtoupper($joinType) === '>' || strtoupper($joinType) === '[>]') {
                    $joinType = 'LEFT JOIN';
                } elseif (strtoupper($joinType) === '<' || strtoupper($joinType) === '[<]') {
                    $joinType = 'RIGHT JOIN';
                } elseif (strtoupper($joinType) === '<>' || strtoupper($joinType) === '[<>]') {
                    $joinType = 'INNER JOIN';
                }
                
                if (is_string($condition) && strpos($condition, ':') !== false) {
                    // Medoo shorthand: 'table.column:other_table.other_column'
                    list($left, $right) = explode(':', $condition);
                    $joinClauses[] = "{$joinType} {$left} ON {$left} = {$right}";
                } elseif (is_string($condition)) {
                    $joinClauses[] = "{$joinType} {$condition}";
                }
            }
            
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $whereClause = db_build_where($where);
            $sql = "SELECT {$cols} FROM {$table} " . implode(' ', $joinClauses) . $whereClause['sql'];
            // Use FETCH_ASSOC to return associative array (matches Medoo behavior)
            return $database->dbSelect($sql, $whereClause['params'], PDO::FETCH_ASSOC);
        }
        // Medoo class
        return $database->select($table, $join, $columns, $where);
    }
    return [];
}

/**
 * medoo_fetch_callback
 *
 * @param string $table
 * @param array $columns
 * @param array $where
 *
 */
function medoo_fetch_callback($table, $columns, $where)
{
    $database = medoo_init();

    if (is_medoo_database($database)) {
        // If it's Db class, process callback manually
        if (is_db_database($database)) {
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $whereClause = db_build_where($where);
            $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];
            $result = $database->dbSelect($sql, $whereClause['params'], PDO::FETCH_ASSOC);
            return array_map(function ($data) {
                return $data;
            }, $result);
        }
        // Medoo class
        return $database->select($table, $columns, $where, function ($data) {
            return $data;
        });
    }
    return [];
}

/**
 * medoo_get_where
 *
 * @param string $table
 * @param string|array $columns
 * @param array $where
 * @see https://medoo.in/api/get
 *
 */
function medoo_get_where($table, $columns, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, build SQL query and return first result
        if (is_db_database($database)) {
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $whereClause = db_build_where($where);
            $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];
            // Use FETCH_ASSOC to return associative array (matches Medoo behavior)
            $result = $database->dbSelect($sql, $whereClause['params'], PDO::FETCH_ASSOC);
            return !empty($result) ? $result[0] : null;
        }
        // Medoo class
        return $database->get($table, $columns, $where);
    }
    return "";
}

/**
 * medoo_get_join
 *
 * @param string $table
 * @param array $join
 * @param string|array $columns
 * @param array $where
 * @return void
 */
function medoo_get_join($table, $join, $columns, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, build SQL manually
        if (is_db_database($database)) {
            $joinClauses = [];
            foreach ($join as $type => $condition) {
                $joinType = trim($type);
                if (strtoupper($joinType) === '>' || strtoupper($joinType) === '[>]') {
                    $joinType = 'LEFT JOIN';
                } elseif (strtoupper($joinType) === '<') {
                    $joinType = 'RIGHT JOIN';
                }
                
                if (is_string($condition) && strpos($condition, ':') !== false) {
                    list($left, $right) = explode(':', $condition);
                    $joinClauses[] = "{$joinType} {$left} ON {$left} = {$right}";
                } elseif (is_string($condition)) {
                    $joinClauses[] = "{$joinType} {$condition}";
                }
            }
            
            $cols = is_array($columns) ? implode(', ', $columns) : $columns;
            $whereClause = db_build_where($where);
            $sql = "SELECT {$cols} FROM {$table} " . implode(' ', $joinClauses) . $whereClause['sql'];
            // Use FETCH_ASSOC to return associative array (matches Medoo behavior)
            $result = $database->dbSelect($sql, $whereClause['params'], PDO::FETCH_ASSOC);
            return !empty($result) ? $result[0] : null;
        }
        // Medoo class
        return $database->get($table, $join, $columns, $where);
    }
    return null;
}

/**
 * medoo_insert
 *
 * @param string $table
 * @param array $values
 *
 */
function medoo_insert($table, $values)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, use dbInsert
        if (is_db_database($database)) {
            return $database->dbInsert($table, $values);
        }
        // Medoo class
        return $database->insert($table, $values);
    }
    return false;
}

/**
 * medoo_update
 *
 * @param string $table
 * @param array $data
 * @param array $where
 * @return int|bool
 */
function medoo_update($table, $data, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, use dbUpdate
        if (is_db_database($database)) {
            return $database->dbUpdate($table, $data, $where);
        }
        // Medoo class
        return $database->update($table, $data, $where);
    }
    return false;
}

/**
 * medoo_replace
 *
 * @param string $table
 * @param array $data
 * @param array $where
 * @return bool
 *
 */
function medoo_replace($table, $data, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, use dbReplace
        if (is_db_database($database)) {
            // Medoo's replace is INSERT ... ON DUPLICATE KEY UPDATE
            // Db class has dbReplace which does the same
            // We need to separate insert values and update values
            // For simplicity, assume $data contains both insert and update values
            // This is a simplified version - full implementation would need separate params
            return $database->dbReplace($table, $data, $data);
        }
        // Medoo class
        return $database->replace($table, $data, $where);
    }
    return false;
}

/**
 * medoo_delete
 *
 * @param string $table
 * @param array $where
 * @return int|bool
 */
function medoo_delete($table, $where)
{
    $database = medoo_init();
    if (is_medoo_database($database)) {
        // If it's Db class, use dbDelete
        if (is_db_database($database)) {
            return $database->dbDelete($table, $where);
        }
        // Medoo class
        return $database->delete($table, $where);
    }
    return false;
}
