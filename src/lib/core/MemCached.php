<?php
/**
 * MemCache Class extends Cache Class
 * 
 * @category Core Class
 * @author   Evert Pot
 * @see      https://evertpot.com/107/
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class MemCached extends Cache 
{
  /**
   * @var object
   */
  public $meminstance;

/**
 * 
 */
  public function __construct() 
  {
    $this->meminstance = new Memcached();
  }

/**
 * readCache
 * Retrieve an item
 * 
 * @param string $key
 * 
 */
  public function readCache($key)
  {
    return $this->meminstance->get($key);
  }

/**
 * WriteCache
 * set an item
 * 
 * @param string $key
 * @param array $data
 * @param number $expirationTimes
 * 
 */
  public function writeCache($key, $data, $expirationTimes)
  {
    return $this->meminstance->set($key, $data, 0, $expirationTimes);
  }

  public function removeCache($key)
  {
    return $this->meminstance->delete($key);
  }

  public function addServer($host, $port = 11211, $weight = 10)
  {
    
    $this->meminstance->addServer($host, $port, true, $weight);

  }

}