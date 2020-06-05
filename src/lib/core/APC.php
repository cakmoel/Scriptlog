<?php
/**
 * Class APC extends Class Cache
 * This is APC Cache functionality to handle APC implemented by system caches
 * 
 * @category Core Class
 * @author   Evert Pot
 * @see      https://evertpot.com/107/
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class APC extends Cache
{
 
  /**
   * read cache
   * 
   * @param string $key
   * 
   */
  public function readCache($key)
  {
    return apc_fetch($key);
  }

  /**
   * write cache
   * 
   * @param string $key
   * @param string $data
   * @param mixed $timelimit
   * 
   */
  public function writeCache($key, $data, $ttl)
  {
    return apc_store($key, $data, $ttl);
  }

  /**
   * remove cache
   * 
   * @param string $key
   * 
   */
  public function removeCache($key)
  {
    return apc_delete($key);
  }
  
}