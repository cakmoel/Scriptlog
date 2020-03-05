<?php
/**
 * Class DbMySQLi
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */

class DbMySQLi
{

private $dbc;

private $dbhost;

private $dbuser;

private $dbpass;

private $dbname;

private static $report_mode;

public $errors;

public static $inst = null;

public static $counter = 0;

public function __construct()
{

  try {

    $config = [];

    $config = $this->getConfiguration();

    $this->dbhost = $config['db']['host'];
    $this->dbuser = $config['db']['user'];
    $this->dbpass = $config['db']['pass'];
    $this->dbname = $config['db']['name'];

    $this->dbc = new mysqli($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);
      
    if ($this->dbc->connect_errno) {

       throw new mysqli_sql_exception("Error Processing Request", 1);
       
    }

    self::activateReportMode();

  } catch (mysqli_sql_exception $e) {
      
      $this->disconnect();
      $this->errors = LogError::newMessage($e);
      $this->errors = LogError::customErrorMessage('admin');

  }

}

public function __destruct()
{
  
  if ($this->dbc) {

      $this->disconnect();

  }

}

/**
 * Unable active report mode on MySQLi Driver
 *
 * @return void
 * 
 */
public static function activateReportMode()
{
  
  if (self::$report_mode = new mysqli_driver()) {

      return self::$report_mode =  MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

  }

}

public static function getInstance()
{

  if( self::$inst == null ) {

      self::$inst = new DbMySQLi();

  }

  return self::$inst;
    
}

public function disconnect()
{
  $this->dbc->close();
}

public function simpleQuery($sql)
{

  try {
  
    $run_query = $this->dbc->query($sql);

    if ($this->dbc->error) {

        throw new mysqli_sql_exception("Invalid query: ".$this->dbc->error);

        return false;

    } else {

        return true;

    }

  } catch (mysqli_sql_exception $e) {
    
     $this->disconnect();
     $this->errors = LogError::newMessage($e);
     $this->errors = LogError::customErrorMessage();

  }

}

/**
 * mysqli prepared statement
 *
 * @example usage:
 * // populate with data
 *  $sql = "INSERT INTO tmp_mysqli_helper_test (name) VALUES (?),(?),(?)";
 *  $stmt = prepared_query($conn, $sql, ['Sam','Bob','Joe']);
 *  $stmt->affected_rows;
 *  $conn->insert_id;
 * 
 *  // Getting an array of rows
 *  $start = 0;
 *  $limit = 10;
 *  $sql = "SELECT * FROM tmp_mysqli_helper_test LIMIT ?,?";
 *  $all = prepared_query($conn, $sql, [$start, $limit])->get_result()->fetch_all(MYSQLI_ASSOC);
 *  foreach ($all as $row) {
 *    echo "{$row['id']}: {$row['name']}\n"; 
 *  }
 * 
 * @see https://phpdelusions.net/mysqli/simple
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return void
 */
public function preparedQuery($sql, $params, $types = "")
{
  
  $types = $types ?: str_repeat("s", count($params));

  $stmt = $this->dbc->prepare($sql);

  $stmt->bind_param($types, ...$params);
  
  $stmt->execute();
  
  return $stmt;

}

/**
 * A helper function for SELECT queries
 * 
 * @example usage: 
 *    $start = 0;
 *    $limit = 10;
 *    $sql = "SELECT * FROM users LIMIT ?,?";
 *    $user_list = prepared_select($mysqli, $sql, [$start, $limit])->fetch_all(MYSQLI_ASSOC);
 * 
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return void
 */
public function preparedSelect($sql, $params = [], $types = "")
{
  return prepared_query($sql, $params, $types)->get_result();
}

/**
 * INSERT query from an array
 *
 * @param string $table
 * @param array $data
 * @return void
 * 
 */
public function preparedInsert($table, $data)
{

  $keys = array_keys($data);
  $keys = array_map('escape_string', $keys);
  $fields = implode(",", $keys);
  $table = escape_string($table);
  $placeholders = str_repeat('?,', count($keys) - 1). '?';
  $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";

  $this->preparedQuery($sql, array_values($data));

}

public function escape_string($field)
{
   return "`".str_replace("`", "``", $field)."`";
}

/**
 * Filtering user data
 * @example usage:
 * $user_name = $database->filteringUserData( $_POST['user_name'] );
 * 
 * Or to filter an entire array:
 * $data = array( 'name' => $_POST['name'], 'email' => 'email@address.com' );
 * $data = $database->filter( $data );
 * 
 * 
 * @param mixed $data
 * @return mixed
 * 
 */
public function filteringUserData($data)
{

 if (!is_array($data)) {

    $data = trim($this->dbc->real_escape_string($data));
    $data = purify_dirty_html($data);
    
 } else {

    $data = array_map( array( $this, 'filter' ), $data );

 }

 return $data;

}

public function cleanOutputDisplay($data)
{

  $data = stripslashes( $data );
  $data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
  $data = nl2br( $data );
  $data = urldecode( $data );
  return $data;

}

public function isTableExists($table_name)
{
  self::$counter++;

  $check = $this->dbc->query("SELECT 1 FROM $table_name");

  if ($check !== false) {

      if ($check->num_rows > 0) {

          return true;

      } else {

          return false;

      }

  } else {

      return false;

  }
  
}

public function getNumRows($sql)
{
  
 try {
   
  self::$counter++;

  $num_rows = $this->dbc->query($sql);

  if ($this->dbc->error) {

     throw new mysqli_sql_exception($this->dbc->error);

  } else {

      return $num_rows->num_rows;

  }

 } catch (mysqli_sql_exception $e) {
   
    $this->errors = LogError::newMessage($e);
    $this->errors = LogError::customErrorMessage();

 }

}

private function getConfiguration()
{

  try {
  
    $filename = __DIR__ . '/../../config.php';

    if (!check_config_file($filename)) {

      throw new DbException("Database configuration not Found");

    }
  
    $config = AppConfig::readConfiguration($filename);

    return $config;

  } catch (DbException $e) {
    
      $this->errors = LogError::newMessage($e);
      $this->errors = LogError::customErrorMessage('admin');

  }
  
}

}