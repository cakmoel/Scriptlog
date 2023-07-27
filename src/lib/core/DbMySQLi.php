<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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

private $dbport;

private static $report_mode = [];

private static $config = [];

public $errors;

public static $inst = null;

public static $counter = 0;

public function __construct()
{

  try {

    self::$config = $this->getConfiguration();

    $this->dbhost = self::$config['db']['host'];
    $this->dbuser = self::$config['db']['user'];
    $this->dbpass = self::$config['db']['pass'];
    $this->dbname = self::$config['db']['name'];
    $this->dbport = self::$config['db']['port'];

    $this->dbc = new mysqli($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport);
   
    if ($this->dbc->connect_errno) {

      $this->disconnect();
      throw new mysqli_sql_exception("Error Processing Connection", 1);
      
    }
    
    self::activateReportMode();
    $this->dbc->set_charset('utf8mb4');

  } catch (Throwable $th) {

    $this->errors = LogError::setStatusCode(http_response_code(500));
    $this->errors = LogError::exceptionHandler($th);

  } catch (mysqli_sql_exception $e) {
        
    $this->errors = LogError::setStatusCode(http_response_code(500));
    $this->errors = LogError::exceptionHandler($e);

  }

}

public function __destruct()
{
  
  if ($this->dbc) {

    $this->disconnect();

  }

}

/**
 * activate report mode on MySQLi Driver
 *
 * @return void
 * 
 */
public static function activateReportMode()
{
  
  if (self::$report_mode[] = new mysqli_driver()) {

    self::$report_mode[] =  MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

  }

}

/**
 * getInstance
 *
 * @return object
 * 
 */
public static function getInstance()
{

  if (self::$inst == null) {

      self::$inst = new DbMySQLi();

  }

  return self::$inst;
    
}

/**
 * dbMySQLiTransaction()
 *
 * @return bol
 * 
 */
public function dbMySQLTransaction()
{
  $this->dbc->begin_transaction();
}

/**
 * dbMySQLiCommit()
 *
 */
public function dbMySQLCommit()
{
  $this->dbc->commit();
}

/**
 * dbMySQLiLastInsertId
 *
 */
public function dbMySQLInsertId()
{
  $this->dbc->insert_id;
}

/**
 * disconnect()
 *
 */
public function disconnect()
{
  $this->dbc->close();
}

/**
 * simpleQuery
 *
 * @param string $sql
 * 
 */
public function simpleQuery($sql)
{

  try {
  
    return $this->dbc->query($sql);

  } catch (mysqli_sql_exception $e) {
     
    $this->errors = LogError::setStatusCode(http_response_code(500));
    $this->errors = LogError::exceptionHandler($e);
    $this->disconnect();

  }

}

/**
 * preparedQuery
 * mysqli prepared statement
 *
 * @example usage:
 * // populate with data
 *  $sql = "INSERT INTO tmp_mysqli_helper_test (name) VALUES (?),(?),(?)";
 *  $stmt = $this->preparedQuery($conn, $sql, ['Sam','Bob','Joe']);
 *  $stmt->affected_rows;
 *  $conn->insert_id;
 * 
 *  // Getting an array of rows
 *  $start = 0;
 *  $limit = 10;
 *  $sql = "SELECT * FROM tmp_mysqli_helper_test LIMIT ?,?";
 *  $all = $this->preparedQuery($conn, $sql, [$start, $limit])->get_result()->fetch_all(MYSQLI_ASSOC);
 *  foreach ($all as $row) {
 *    echo "{$row['id']}: {$row['name']}\n"; 
 *  }
 * 
 * @see https://phpdelusions.net/mysqli/simple
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return object
 * 
 */
public function preparedQuery($sql, $params, $types = "")
{
  
  $types = $types ?: str_repeat("s", count($params));

  $stmt = $this->dbc->prepare($sql);

  if (!$stmt) {

    return false;

  } else {

    $stmt->bind_param($types, ...$params);
  
    $stmt->execute();

  }
  
  return $stmt;

}

/**
 * A helper function for SELECT queries
 * 
 * @example usage: 
 *    $start = 0;
 *    $limit = 10;
 *    $sql = "SELECT * FROM users LIMIT ?,?";
 *    $user_list = $this->preparedSelect($mysqli, $sql, [$start, $limit])->fetch_all(MYSQLI_ASSOC);
 * 
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return object
 * 
 */
public function preparedSelect($sql, $params = [], $types = "")
{
  $items = $this->preparedQuery($sql, $params, $types);
  return $items->getResult();
  
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
  $table = $this->escape_string($table);
  $placeholders = str_repeat('?,', count($keys) - 1). '?';
  $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";

  $this->preparedQuery($sql, array_values($data));

}

/**
 * preparedUpdate
 *
 * @param string $table
 * @param array $data
 * @param int $id
 * @return void
 * 
 */
public function preparedUpdate($table, $data, $id)
{

$table = $this->escape_string($table);

$sql = "UPDATE $table SET ";

foreach (array_keys($data) as $d => $field) {
  
    $field = $this->escape_string($field);
    $sql .= ($d) ? ", " : "";
    $sql .= "$field = ?";

}

$sql .= " WHERE ID = ?";

$data[] = (int)$id;

$this->preparedQuery($sql, array_values($data));

}

/**
 * escape_string
 *
 * @param string $field
 * @return string
 * 
 */
public function escape_string($field)
{
   return "`".str_replace("`", "``", $field)."`";
}

/**
 * Filtering user data
 * @example usage:
 * $user_name = $database->filterData( $_POST['user_name'] );
 * 
 * Or to filter an entire array:
 * $data = array( 'name' => $_POST['name'], 'email' => 'email@address.com' );
 * $data = $database->filterData( $data );
 * 
 * 
 * @param mixed $data
 * @return mixed
 * 
 */
public function filterData($data)
{

 if (!is_array($data)) {

    $data = trim($this->escape_string($data));
    $data = purify_dirty_html($data);
    
 } else {

    $data = array_map( array( $this, 'filterData' ), $data );

 }

 return $data;

}

public function cleanOutputDisplay($data)
{

  $data = stripslashes($data);
  $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
  $data = nl2br($data);
  $data = urldecode($data);
  return $data;

}

/**
 * isTableExists
 * checking whether table name exists on database
 * @param string $table_name
 * @see https://stackoverflow.com/questions/6432178/how-can-i-check-if-a-mysql-table-exists-with-php
 * @return boolean
 * 
 */
public function isTableExists($table_name)
{
  $database = $this->dbname;
  $check_table = $this->dbc->query("SHOW TABLES LIKE '$table_name'");
  $check_table_schema = $this->dbc->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$database' AND table_name = '$table_name'");
 
  (($check_table->num_rows > 0) || ($check_table_schema->num_rows == 1) ? true : false); 

  if (is_resource($check_table) || is_resource($check_table_schema)) {

    $check_table->free();

  }
  
}

/**
 * getNumRows
 *
 * @param string $sql
 * @return int|string
 */
public function getNumRows($results)
{
  
 if ($results) {

  if ($this->dbc->error) {

    throw new mysqli_sql_exception($this->dbc->error);

  } else {

    if ($row_count = $results->num_rows) {

      return $row_count;
        
    }

  }

 }
  

}

/**
 * getResult
 * 
 * @param object $Statement
 * @see https://stackoverflow.com/questions/10752815/mysqli-get-result-alternative
 * @return array
 * 
 */
public function getResult($Statement)
{

$result = array();

$Statement->store_result();
    
  for ($i = 0; $i < $Statement->num_rows; $i++) {
        
     $Metadata = $Statement->result_metadata();
        
     $params = array();
        
    while ($Field = $Metadata->fetch_field() ) {
            
      $params[] = &$result[ $i ][ $Field->name ];
        
    }
        
    call_user_func_array( array( $Statement, 'bind_result' ), $params );
        
    $Statement->fetch();
    
  }
    
  return $result;

}

/**
 * getConfiguration
 *
 * @return void
 * 
 */
private function getConfiguration()
{
  return AppConfig::readConfiguration(invoke_config());
}

}