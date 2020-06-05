<?php 
/**
 * Dao Class
 * Data Access Object
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Dao
{
 /**
  * Database connection 
  * @var string
  */
 protected $dbc;
 
 /**
  * SQL
  * @var string
  */
 protected $sql;
 
 /**
  * Error
  * @var string
  */
 protected $error;
 
 /**
  * Sanitize
  * @var object
  */
 protected $sanitizing;

 /**
  * 
  */
 public function __construct() 
 {
   if (Registry::isKeySet('dbc')) {

       $this->dbc = Registry::get('dbc');

   }
 }

 public function __desctruct()
 {
   Registry::set('dbc', null);
   session_write_close();
 }

 /**
  * Set SQL
  * @param string $sql
  */
 protected function setSQL($sql)
 {
    $this->sql = $sql;
 }
 
 /**
  * Find All records
  * getting array of rows
  * 
  * @param array $data
  * @param PDO::FETCH_MODE static $fetchMode
  * @throws DbException
  * @return array|object
  */
 protected function findAll($data = null, $fetchMode = null)
 {
     
   try {
        
        if (!$this->sql) {

            throw new DbException("No SQL Query");

        }
        
        $findAll = (!is_null($fetchMode)) ? $this->dbc->dbQuery($this->sql, $data)->fetchAll($fetchMode) : $this->dbc->dbQuery($this->sql, $data)->fetchAll();

        return $findAll;
        
    } catch (DbException $e) {
    
        $this->closeConnection();
        $this->error = LogError::newMessage($e);
        $this->error = LogError::customErrorMessage('admin');
        
    }
    
 }
 
/**
  * Find Single row record
  * getting one row
  * 
  * @param array $data
  * @param PDO::FETCH_MODE static $fetchMode
  * @throws DbException
  * @return array|object
  *
  */
 protected function findRow($data = null, $fetchMode = null)
 {
     
  try {
    
    if (!$this->sql) {
    
        throw new DbException("No SQL Query!");
    }
     
    $findRow = (!is_null($fetchMode)) ? $this->dbc->dbQuery($this->sql, $data)->fetch($fetchMode) : $this->dbc->dbQuery($this->sql, $data)->fetch();

    return $findRow;

  } catch (DbException $e) {
     
      $this->closeConnection();
      $this->error = LogError::newMessage($e);
      $this->error = LogError::customErrorMessage('admin');
  }   
  
 }
 
 /**
  * Find Column
  * return a single column from the next row of results set
  * getting single field value
  * 
  * @param array $data
  * @param PDO::FETCH_MODE static $fetchMode
  * @throws DbException
  * @return boolean false if no more rows
  */
 protected function findColumn($data = null, $fetchMode = null)
 {
     
   try {
    
       if (!$this->sql) {
           
          throw new DbException("No SQL Query!");
           
       }
       
       $findColumn = (!is_null($fetchMode)) ? $this->dbc->dbQuery($this->sql, $data)->fetchColumn($fetchMode) : $this->dbc->dbQuery($this->sql, $data)->fetchColumn();

       return $findColumn;

   } catch (DbException $e) {
       
       $this->closeConnection();
       $this->error = LogError::newMessage($e);
       $this->error = LogError::customErrorMessage('admin');
       
   }
     
 }
 
 /**
  * CheckCountValue function
  *
  * @param array $data
  * @throws DbException
  * @return integer|numeric
  *
  */
 protected function checkCountValue($data = null)
 {
     
   try {
         
        if (!$this->sql) {
             
           throw new DbException("No SQL Query!");
           
        }
         
        $stmt = $this->dbc->dbQuery($this->sql, $data);
         
        return $stmt->rowCount();
              
     } catch (DbException $e) {
         
        $this->closeConnection();
        $this->error = LogError::newMessage($e);
        $this->error = LogError::customErrorMessage('admin');
         
     }
     
 }
 
 /**
  * Create records
  * 
  * @param string $table
  * @param array $params
  *
  */
 protected function create($table, $params)
 {
    $this->dbc->dbInsert($table, $params);
 }
 
 /**
  * Modify record
  * 
  * @param string $table
  * @param array $params
  * @param integer|string $where
  *
  */
 protected function modify($table, $params, $where)
 {
    $this->dbc->dbUpdate($table, $params, $where);
 }
 
 /**
  * Delete record
  * 
  * @param string $table
  * @param integer $where
  * @param integer $limit
  */
 protected function deleteRecord($table, $where, $limit = null)
 {
    (!is_null($limit)) ? $this->dbc->dbDelete($table, $where, $limit) : $this->dbc->dbDelete($table, $where);
 }
 
 /**
 * startTransaction
 * begin transaction for multiple queries as a unified block 
 * 
 * @return void
 * 
 */
protected function callTransaction()
{
  $this->dbc->dbTransaction();
}

/**
 * callCommit
 * commit the transaction if no problems have been encountered
 * 
 */
protected function callCommit()
{
  $this->dbc->dbCommit();
}

/**
 * callRollBack
 * to roll back the tables to their original state.
 * 
 */
protected function callRollBack()
{
  $this->dbc->dbRollBack();
}

 /**
  * Close database connection
  * 
  * @return bool
  */
 protected function closeConnection()
 {
   $this->dbc->closeDbConnection();
 }
 
 /**
  * Last insert Id
  * @return integer
  */
 protected function lastId()
 {
   return $this->dbc->dbLastInsertId();
 }
 
/**
 * Filtering Id passed by HTTP request
 *  
 * @param object $sanitize
 * @param string $str
 * @param string $type
 * @return 
 * 
 */
 protected function filteringId(Sanitize $sanitize, $str, $type)
 {

 try {

   $this->sanitizing = $sanitize;
	 	
   switch ($type) {
      
      case 'sql':
        
          if (filter_var($str, FILTER_SANITIZE_NUMBER_INT)) {
              
            return $this->sanitizing->sanitasi($str, 'sql');
              
          } else {
              
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new DbException("ERROR: this - $str - Id is considered invalid.");
              
          }
          
          break;
      
      case 'xss':
            
          if (filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) {
              
            return $this->sanitizing->sanitasi(prevent_injection($str), 'xss');
              
          } else {
              
              header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
              throw new DbException("ERROR: this - $str - is considered invalid.");

          }
          
          break;
      
       }
	 	
      } catch (DbException $e) {
	 	
        $this->error = LogError::setStatusCode(http_response_code());
        $this->error = LogError::newMessage($e);
        $this->error = LogError::customErrorMessage();
          
      }
			
}
	
}