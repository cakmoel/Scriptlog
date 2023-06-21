<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class SearchFinder
 * Searching keyword from search functionality form
 *
 * @category  Core Class
 * @author    Maoelana Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class SearchFinder
{

/**
 * Database connection
 * 
 * @var object
 * 
 */
 private $dbc;
 
/**
 * errorss
 * 
 * @var object
 * 
 */
 protected $errors;

/**
 * SQL
 * 
 * @var string
 * 
 */
 public $sql;
 
/**
 * Results from SQL Query
 * 
 * @var string
 * 
 */
 public $results;
 
/**
 * Bind data
 * 
 * @var string
 * 
 */
 public $bind;

/**
 * Initialize object properties and method
 * and an instance of database connection
 * 
 * @param object $dbc
 * 
 */
 public function __construct()
 {
	
  if (Registry::isKeySet('dbc')) {

	$this->dbc = Registry::get('dbc');
  
  }

 }
 
 public function __destruct()
 {
	Registry::set('dbc', null);
	session_write_close();
 }

/**
 * Clean Up
 * 
 * @param array $bind
 * @return array
 * 
 */
 public function cleanUp($bind)
 {
  return $bind;
 }

/**
 * Binding statement
 * 
 * @param string $sql
 * @param string $bind
 * @return bool
 * 
 */
 public function bindStatement($sql, $bind = "")
 {
  $this->sql = $sql;
  $this->bind = $this->cleanUp($bind);
  $this->errors = '';
  
  try {
  	
  	$sth = $this->dbc->prepare($this->sql);
  	
  	if ($sth -> execute($this->bind) !== false ) {
  		
  		if (preg_match("/^(" . implode("|", array ("select", "describe", "pragma")) . ") /i", $this->sql)) {

			  return $sth->fetchAll(PDO::FETCH_ASSOC);
			  
  		} elseif (preg_match("/^(" . implode("|", array ("delete", "insert", "update")) . ") /i", $this->sql)) {

			  return $sth->rowCount();
			  
  		}
  		
  	}
  	
  } catch (Throwable $th) {

	$this->errors = LogError::setStatusCode(http_response_code(500));
	$this->errors = LogError::exceptionHandler($th);

  } catch (DbException $e) {
  	
  	$this->errors = LogError::setStatusCode(http_response_code(500));
  	$this->errors = LogError::exceptionHandler($e);
  	
  }
  
  return false;
  
 }
 
/**
 * Set Query
 * 
 * @param string $sql
 * @param bool $bind
 * @return bool
 * 
 */
 public function setQuery($sql, $bind = false)
 {
  $this->errors = '';
  
  try {
  	
  	if ($bind !== false) {
  	
  	 return $this->bindStatement($sql, $bind);
  	 
  	} else {
  		
  		$this->results = $this->dbc->query($sql);
  		return $this->results;
  	}
  	
  } catch (Throwable $th) {

	$this->errors = LogError::setStatusCode(http_response_code());
	$this->errors = LogError::exceptionHandler($th);

  } catch (PDOException $e) {
  	
  	$this->errors = LogError::setStatusCode(http_response_code());
  	$this->errors = Logerror::exceptionHandler($e);
  	
  }
  
  return false;
  
 }
 
/**
 * Searching Post especially blog post
 * 
 * @param string $data
 * @return mixed
 * 
 */
 public function searchPost($data)
 {
    
    $bind = array(":keyword1" => "%$data%", ":keyword2" => "%$data%", ":keyword3" => "%$data%");
     
 	$this->sql = "SELECT 
                     ID,
                     post_author, post_created, post_modified, 
                     post_title, post_slug, 
                     post_content, post_status, 
                     post_type
                 FROM 
                    tbl_posts
                 WHERE 
                    post_title LIKE :keyword1 
					OR post_content LIKE :keyword2
					OR post_tags LIKE :keyword3
                    AND post_status = 'publish' AND post_type = 'blog' ";
 	             
 	
 	$results = $this->setQuery($this->sql, $bind); // hasil pencarian
 	
 	$sth = $this->dbc->prepare($this->sql);
 	$keyword = '%'.$data.'%';
 	$sth->bindValue(':keyword1', $keyword, PDO::PARAM_STR);
 	$sth->bindValue(':keyword2', $keyword, PDO::PARAM_STR);
	$sth->bindValue(':keyword3', $keyword, PDO::PARAM_STR);
 	$sth->execute();
 	$totalRows = $sth->rowCount();
 	
 	return array("results" => $results, "totalRows" => $totalRows);
 	
 }
 
}