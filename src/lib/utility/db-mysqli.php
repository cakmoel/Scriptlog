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

function db_simple_query($sql)
{
  
  $stmt = db_instance()->simpleQuery($sql);

  return $stmt;

}

/**
 * mysqli prepared statement
 * $sql = DML
 * 
 * @param string $sql
 * @param array $params
 * @return void
 */
function db_prepared_query($sql, $params)
{
  
  $stmt = db_instance()->preparedQuery($sql, $params);

  return $stmt;

}

function is_table_exists($table)
{

  $is_exists = db_instance()->isTableExists($table);

  return $is_exists;

}

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

