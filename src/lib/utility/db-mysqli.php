<?php
/**
 * collection of function uses MySQL Improved (MySQLi) extension
 * 
 * @category function
 * @uses DbMySQLi::getInstance
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */

// database connection 
function db_instance()
{
  $database = DbMySQLi::getInstance();

  return $database;
}

// database query
function db_simple_query($sql)
{
  
  $stmt = db_instance()->simpleQuery($sql);

  return $stmt;

}

/**
 * db_prepared_query function
 * $sql = DML
 * 
 * @param string $sql
 * @param array $params
 * @return void
 */
function db_prepared_query($sql, $params, $types)
{
  
  $stmt = db_instance()->preparedQuery($sql, $params, $types);

  return $stmt;

}

/**
 * is_table_exists function
 * Checking whether table name exists on database
 * 
 * @param string $table
 * @return boolean
 */
function is_table_exists($table)
{

  $is_exists = db_instance()->isTableExists($table);

  return $is_exists;

}

/**
 * check_table function
 * This function will check all of tables needed for your weblog 
 * @return void
 */
function check_table()
{

  $dbscheme = false;

  if (!(is_table_exists('tbl_comments') || (is_table_exists('tbl_comment_reply') || (is_table_exists('tbl_login_attempt')) 
      || (is_table_exists('tbl_media') || (is_table_exists('tbl_mediameta') || (is_table_exists('tbl_media_download') 
      || (is_table_exists('tbl_menu') || (is_table_exists('tbl_menu_child') || (is_table_exists('tbl_plugin') 
      || (is_table_exists('tbl_posts') || (is_table_exists('tbl_post_topic') || (is_table_exists('tbl_settings') 
      || (is_table_exists('tbl_themes') || (is_table_exists('tbl_topics') || (is_table_exists('tbl_users') 
      || (is_table_exists('tbl_user_token'))))))))))))))))) {

        $dbscheme = false;

  } else {

      $dbscheme = true;

  }

  return $dbscheme;

}

