<?php
/**
 * collection of function uses MySQL Improved (MySQLi) extension
 * 
 * @category function
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


/**
 * db mysqli query
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

function sanitizing_data($sanitizer, $str, $type)
{
   
try {
  
  if (is_object($sanitizer)) {


   switch ($type) {
 
    case 'sql':
      
      if (filter_var($str, FILTER_SANITIZE_NUMBER_INT)) {
              
        return $sanitizer->sanitasi($str, 'sql');
          
      } else {
          
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
        throw new DbException("ERROR: this - $str - Id is considered invalid.");
          
      }
      
      break;
    
    case 'xss':

      if (filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) {
              
        return $sanitizer->sanitasi(prevent_injection($str), 'xss');
          
      } else {
          
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new DbException("ERROR: this - $str - is considered invalid.");

      }
      
      break;

   }

  }

} catch (DbException $e) {

  $this->error = LogError::setStatusCode(http_response_code());
  $this->error = LogError::newMessage($e);
  $this->error = LogError::customErrorMessage();

}

}