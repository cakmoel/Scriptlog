<?php
/**
 * collection of function uses MySQL Improved (MySQLi) extension
 * 
 * @category function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */

// database connection 
function db_connect($host, $user, $passwd, $dbname)
{

  mysqli_driver(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  
  $database_connection = mysqli_connect($host, $user, $passwd, $dbname);

  if(mysqli_connect_errno($database_connection)) {
    scriptlog_error('Failed to connect to MySQL '.mysqli_connect_error(), E_USER_ERROR);
    exit();
  }

  return $database_connection;

}

// close database connection
function db_close($connection)
{
 return mysqli_close($connection);
}

// query table name 
function table_exists($connection, $table, $counter = 0)
{
  if ($connection) {
    $counter++;

    $check = mysqli_query("SHOW TABLES LIKE '".$table."'");

    if($check !== false) {

       if(mysqli_num_rows($check) > 0) {

         return true;

       } else {

         return false;

       }

    }

  }

}

// check whether database table does exist 
function check_table($connection, $dbname)
{
  
  $install = false;

  if(!table_exists($link, $table)) {
      
    $install = true;

  } else {

    $install = false;

  }

  return $install;

}

// 
function db_query($connection, $query_type, $table_name, $SQL)
{
  if (mysqli_query($connection, $SQL)) {

     print("$query_type query for $table_name - success");

  }

}
