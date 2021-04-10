<?php
/**
 * db_instance
 * An instance of DbMySQLi Class
 * 
 * @category function db_instace()
 * @author M.Noermoehammad
 * @license MIT
 * @uses DbMySQLi::getInstance
 * @version 1.0
 * @return object
 * 
 */
function db_instance()
{

  return DbMySQLi::getInstance();

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
  
  return db_instance()->simpleQuery($sql);

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
function db_prepared_query($sql, $params, $types)
{
  
  return db_instance()->preparedQuery($sql, $params, $types);
  
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

  $is_exists = db_instance()->isTableExists($table);

  yield $is_exists;

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

  if (!(is_table_exists('tbl_comments') 
      || is_table_exists('tbl_comment_reply') 
      || is_table_exists('tbl_login_attempt')
      || is_table_exists('tbl_media') 
      || is_table_exists('tbl_mediameta') 
      || is_table_exists('tbl_media_download') 
      || is_table_exists('tbl_menu') 
      || is_table_exists('tbl_plugin') 
      || is_table_exists('tbl_posts') 
      || is_table_exists('tbl_post_topic') 
      || is_table_exists('tbl_settings') 
      || is_table_exists('tbl_themes') 
      || is_table_exists('tbl_topics') 
      || is_table_exists('tbl_users') 
      || is_table_exists('tbl_user_token'))) {

        $dbscheme = false;

  } else {

      $dbscheme = true;

  }

  yield $dbscheme;

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
    for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
        $Metadata = $Statement->result_metadata();
        $params = array();
        while ( $Field = $Metadata->fetch_field() ) {
            $params[] = &$result[ $i ][ $Field->name ];
        }
        call_user_func_array( array( $Statement, 'bind_result' ), $params );
        $Statement->fetch();
    }
    
  return $result;

}