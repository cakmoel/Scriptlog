<?php

/**
 * db_instance
 * An instance of DbMySQLi Class
 *
 * @category function db_instace()
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return object
 *
 */
function db_instance()
{
    // Use existing connection from Registry if available (includes table prefix)
    if (class_exists('Registry')) {
        $dbc = Registry::get('dbc');
        if (is_object($dbc)) {
            return $dbc;
        }
    }

    // Fallback: create new instance
    if (!class_exists('DbMySQLi')) {
        require_once APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'DbMySQLi.php';
    }
    return DbMySQLi::getInstance();
}

/**
 * db_begin_transaction
 *
 * @return bool
 *
 */
function db_begin_transaction()
{
    $db = db_instance();
    if (method_exists($db, 'dbMySQLTransaction')) {
        return $db->dbMySQLTransaction();
    } elseif (method_exists($db, 'dbTransaction')) {
        return $db->dbTransaction();
    }
    return false;
}

/**
 * db_commit
 *
 */
function db_commit()
{
    $db = db_instance();
    if (method_exists($db, 'dbMySQLCommit')) {
        return $db->dbMySQLCommit();
    } elseif (method_exists($db, 'dbCommit')) {
        return $db->dbCommit();
    }
    return false;
}

/**
 * db_insert_id()
 *
 */
function db_insert_id()
{
    $db = db_instance();
    if (method_exists($db, 'dbMySQLInsertId')) {
        return $db->dbMySQLInsertId();
    } elseif (method_exists($db, 'dbLastInsertId')) {
        return $db->dbLastInsertId();
    }
    return null;
}

/**
 * db_simple_query
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $sql
 * @return object
 *
 */
function db_simple_query($sql)
{
    $db = db_instance();
    // Use dbQuery for Db class, or simpleQuery for mysqli
    if (method_exists($db, 'dbQuery')) {
        return $db->dbQuery($sql);
    } elseif (method_exists($db, 'simpleQuery')) {
        return $db->simpleQuery($sql);
    }
    return null;
}

/**
 * db_prepared_query
 * $sql = DML
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $sql
 * @param array $params
 * @return object
 *
 */
function db_prepared_query($sql, array $params, $types = "")
{
    $db = db_instance();
    // Use preparedQuery for mysqli, or dbQuery with parameters for Db class
    if (method_exists($db, 'preparedQuery')) {
        return $db->preparedQuery($sql, $params, $types);
    } elseif (method_exists($db, 'dbQuery')) {
        return $db->dbQuery($sql, $params);
    }
    return null;
}

/**
 * is_table_exists
 * Checking whether table name exists on database
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $table
 * @return boolean
 *
 */
function is_table_exists($table)
{
    $db = db_instance();
    if (method_exists($db, 'isTableExists')) {
        return $db->isTableExists($table);
    } elseif (method_exists($db, 'dbSelect')) {
        try {
            $result = $db->dbSelect("SELECT 1 FROM {$table} LIMIT 1", [], PDO::FETCH_NUM);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    return false;
}

/**
 * db_num_rows()
 * @return int|string
 */
function db_num_rows($results)
{
    $db = db_instance();
    if (method_exists($db, 'getNumRows')) {
        return $db->getNumRows($results);
    } elseif (is_array($results)) {
        return count($results);
    } elseif ($results instanceof PDOStatement) {
        return $results->rowCount();
    }
    return 0;
}

/**
 * check_table
 * This function will check all of tables needed for your weblog
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return void
 *
 */
function check_table()
{

    $dbscheme = false;

    if (
        (APP_DEVELOPMENT === true) &&
        (! (is_table_exists('tbl_comments') ||
        is_table_exists('tbl_login_attempt') ||
        is_table_exists('tbl_media') ||
        is_table_exists('tbl_mediameta') ||
        is_table_exists('tbl_media_download') ||
        is_table_exists('tbl_menu') ||
        is_table_exists('tbl_plugin') ||
        is_table_exists('tbl_posts') ||
        is_table_exists('tbl_post_topic') ||
        is_table_exists('tbl_settings') ||
        is_table_exists('tbl_themes') ||
        is_table_exists('tbl_topics') ||
        is_table_exists('tbl_users') ||
        is_table_exists('tbl_user_token')))
    ) {
        $dbscheme = false;
    } else {
        $dbscheme = true;
    }

    return $dbscheme;
}

/**
 * get_result
 *
 * Example: withoud mysqlnd on the server
 * $Statement = $Database->prepare( 'SELECT x FROM y WHERE z = ?' );
 * $Statement->bind_param( 's', $z );
 * $Statement->execute();
 * $RESULT = get_result( $Statement );
 * while ( $DATA = array_shift( $RESULT ) ) {
 *    // Do stuff with the data
 * }
 *
 * @category function
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @param string $statement
 * @see https://stackoverflow.com/questions/10752815/mysqli-get-result-alternative
 * @return array
 *
 */
function get_result($Statement)
{

    $result = array();
    $Statement->store_result();
    for ($i = 0; $i < $Statement->num_rows; $i++) {
        $Metadata = $Statement->result_metadata();
        $params = array();
        while ($Field = $Metadata->fetch_field()) {
            $params[] = &$result[ $i ][ $Field->name ];
        }
        call_user_func_array(array($Statement, 'bind_result'), $params);
        $Statement->fetch();
    }

    return $result;
}

/**
 * db_close
 *
 * @category function to close database connection
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
function db_close()
{
    $db = db_instance();
    if (method_exists($db, 'disconnect')) {
        return $db->disconnect();
    } elseif (method_exists($db, 'closeDbConnection')) {
        return $db->closeDbConnection();
    }
    return false;
}
