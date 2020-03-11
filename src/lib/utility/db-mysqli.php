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

  if (is_array($table)) {

      foreach ($table as $tbl) {

          if (db_instance()->isTableExists($tbl)) {

              return false;

          } else {

              return true;

          }

      }

  } else {

    $is_exists = db_instance()->isTableExists($table);

    return $is_exists;
      
  }
  
}

function check_table()
{
  
  $install = false;

  if(is_table_exists(database_default_table())) {
      
    $install = false;

  } else {

    $install = true;

  }

  return $install;

}

function database_default_table()
{

  $default_table = [
    'tbl_comments', 'tbl_comment_reply', 'tbl_login_attempt', 'tbl_media', 'tbl_mediameta', 
    'tbl_media_download', 'tbl_menu', 'tbl_menu_child', 'tbl_plugin', 'tbl_posts', 
    'tbl_post_topic', 'tbl_settings', 'tbl_themes', 'tbl_topics', 'tbl_users', 'tbl_user_token'];

    return $default_table;

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