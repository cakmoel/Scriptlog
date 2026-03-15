<?php defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * BaseModel Class
 * 
 * Base Model class providing common functionality for all model classes
 * Extends Dao to inherit database operations
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 * 
 */
class BaseModel extends Dao
{
  
  /**
   * link property for pagination
   * 
   * @var object
   */
  protected $link;

  /**
   * pagination property
   * 
   * @var string
   */
  protected $pagination;

  /**
   * Constructor
   * 
   * Initialize BaseModel and parent Dao
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Get the database connection
   * 
   * @return object
   */
  protected function getDbConnection()
  {
    return $this->dbc;
  }

  /**
   * Get current SQL query
   * 
   * @return string
   */
  protected function getSql()
  {
    return $this->sql;
  }

  /**
   * Set pagination link
   * 
   * @param object $link
   */
  protected function setLink($link)
  {
    $this->link = $link;
  }

  /**
   * Get pagination link
   * 
   * @return object
   */
  protected function getLink()
  {
    return $this->link;
  }

  /**
   * Set pagination
   * 
   * @param string $pagination
   */
  protected function setPagination($pagination)
  {
    $this->pagination = $pagination;
  }

  /**
   * Get pagination
   * 
   * @return string
   */
  protected function getPagination()
  {
    return $this->pagination;
  }

}
