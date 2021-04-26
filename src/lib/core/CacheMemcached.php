<?php
/**
 * Class CacheMemcached
 * 
 * @category Core Class CacheMemcaced
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
class CacheMemcached
{
  
  const MEMCACHED_TTL = 600;

  const MEMCACHED_HOST = '127.0.0.1';

  const MEMCACHED_PORT =  '11211';

  private static $caching;

  public function __constructor(MemCached $memcached)
  {

     if (true === run_cache_engine()) {

        self::$caching = $memcached;
        
        $this->connect();

     }

  }
  
  public function connect()
  {
    return self::$caching->addServer(self::MEMCACHED_HOST, self::MEMCACHED_PORT);
  }

  public function storeCache($key, $data)
  {
     try {

       $item = self::$caching->writeCache(set_cache_key($key, $data), $data);
    
       if (false === $item) {

          throw new CacheException("Memcached set failure. Key: $key");

       }

     } catch(CacheException $e) {

      LogError::setStatusCode(http_response_code(500));
      LogError::exceptionHandler($e);
       
     }

  }

  public function fetchCache($key)
  {
    return self::$caching->readCache($key);
  }

  public function deleteCache($key)
  {
    
    try {

      $item = self::$caching->removeCache($key);

      if (false === $item) {

        throw new CacheException("Memcached delete failure. Key: $key");

      }

    } catch(CacheException $e) {

      LogError::setStatusCode(http_response_code(500));
      LogError::exceptionHandler($e);

    }

  }

}