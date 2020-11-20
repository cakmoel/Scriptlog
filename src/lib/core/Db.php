<?php 
/**
 * Class Db implements DbInterface
 * This Class DB uses PDO-MySQL functionality 
 * and it's methods implemented from DbInterface
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Db implements DbInterface
{
 
 /**
  * database connection
  * @var object
  *
  */
 private $dbc;

/**
 * cache
 * @var object
 * 
 */
 private static $cache;
 
 /**
  * error
  * @var object
  *
  */
 private $error;

/**
 * Initializing an object property and method
 * 
 */
 public function __construct($values, $options)
 {
    $this->setDbConnection($values, $options);
 }
 
 /**
  * set database connection
  * {@inheritDoc}
  * @see DbInterface::setDbConnection()
  * @return object
  * 
  */
 public function setDbConnection($values = [], $options = [])
 {
     
   try {
       
      $dsn = $values[0];
      $dbUser = $values[1];
      $dbPass = $values[2];
      
      $default_options = [
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
      ];
      
      $options = array_replace($default_options, $options);
      
      $this->dbc = new PDO($dsn, $dbUser, $dbPass, $options);
      
      if (!$this->dbc) {

          throw new DbException("Connection Failed!");
          
      }
       
   } catch (DbException $e) {
       
      $this->closeDbConnection();
      $this->error = LogError::newMessage($e);
      $this->error = LogError::customErrorMessage('admin');
      
   }
   
 }
 
 /**
  * close database connection
  */
 public function closeDbConnection()
 {
   $this->dbc = null;
 }
 
 /**
  * SQL - Query
  * 
  * {@inheritDoc}
  * @see DbInterface::dbQuery()
  * @return object
  *
  */
 public function dbQuery($sql, $args = array())
 {
     if (!$args) {
         
        return $this->dbc->query($sql);
         
     }
     
     $stmt = $this->dbc->prepare($sql);

     
     $stmt->execute($args);
     
     return $stmt;
     
 }
 
 /**
  * Insert record
  * 
  * {@inheritDoc}
  * @see DbInterface::dbInsert()
  */
 public function dbInsert($tablename, array $params)
 {
    
    $clean_table = Sanitize::mildSanitizer($tablename);

    try {
        
        $sql = "INSERT INTO $clean_table ";
        $fields = array_keys($params);
        $values = array_values($params);

        $sql .= '('.implode(',', $fields).') ';

        $args = [];
        foreach ($fields as $f) {
            $args[] = '?';
        }
        $sql .= 'VALUES ('.implode(',', $args).') ';

        $statement = $this->dbc->prepare($sql);
        
        foreach ($values as $i => $v) {
            $statement->bindValue($i+1, $v);
        }

        return $statement->execute();

    } catch (DbException $e) {
        
       $this->closeDbConnection();
       $this->error = LogError::newMessage($e);
       $this->error = LogError::customErrorMessage('admin');
       
    }
    
 }
 
 /**
  * last insert id
  * 
  * {@inheritDoc}
  * @see DbInterface::dbLastInsertId()
  */
 public function dbLastInsertId()
 {
    return $this->dbc->lastInsertId();
 }
 
 /**
  * Update record
  * {@inheritDoc}
  * @see DbInterface::dbUpdate()
  */
 public function dbUpdate($tablename, $params, $where)
 {
    
   $clean_table = Sanitize::mildSanitizer($tablename);
   $clean_where = Sanitize::severeSanitizer($where);
   
   try {
       
       ksort($params);

       $columns = null;
       
       foreach ((array)$params as $key => $value) {
           
           $columns .= "$key = :$key,";
           
       }
       
       $columns = rtrim($columns, ',');
       
       $stmt = $this->dbc->prepare("UPDATE $clean_table SET $columns WHERE $clean_where");

       foreach ((array)$params as $key => $value) {
           $stmt->bindValue(":$key", $value);
       }
       
       $stmt->execute();
       
       return $stmt->rowCount();
       
   } catch (DbException $e) {
       
       $this->closeDbConnection();
       $this->error = LogError::newMessage($e);
       $this->error = LogError::customErrorMessage();
       
   }   
   
 }
 
 /**
  * delete record
  * 
  * {@inheritDoc}
  * @see DbInterface::dbDelete()
  */
 public function dbDelete($tablename, $where, $limit = null)
 {
    $clean_table = Sanitize::mildSanitizer($tablename);
    $clean_where = Sanitize::severeSanitizer($where);

    try {
        
        if (!is_null($limit)) {
            
            $sql = "DELETE FROM $clean_table WHERE $clean_where LIMIT $limit";
        
        } else {
            
            $sql = "DELETE FROM $clean_table WHERE $clean_where";
        }
        
        $stmt = $this->dbc->prepare($sql);
        
        $stmt->execute();

        return $stmt->rowCount();
        
    } catch (DbException $e) {
        
        $this->closeDbConnection();
        $this->error = LogError::newMessage($e);
        $this->error = LogError::customErrorMessage();
        
    }
    
 }

/**
 * database transaction
 * 
 * {@inheritDoc}
 * @see DbInterface::dbTransaction
 * 
 */
public function dbTransaction()
{
  return $this->dbc->BeginTransaction();
}

/**
 * database commit
 * 
 * @inheritDoc
 * @see DbInterface::dbCommit
 *
 */
public function dbCommit()
{
  return $this->dbc->commit();
}

/**
 * database rollback
 * 
 * @inheritDoc
 * @see DbInterface::dbRollBack
 * 
 */
public function dbRollBack()
{
  return $this->dbc->rollBack();
}

}