<?php
/**
 * Collection of functions 
 * to use memcached functionality
 * 
 * @category Function to use memcached class
 * 
 */

function set_cache_key($key, $query)
{
  
  $queryKey = $key.md5($query);

  return $queryKey;

}

function use_memcached($key, $data, $args)
{
  
  $cached = null;

  $cache_init = new CacheMemcached();

  switch ($args) {

     case 'set':

       $cached = $cache_init -> storeCache($key, $data);

       break;
       
    case 'get':

       $cached = $cache_init -> fetchCache($key);

       break;

    case 'delete':
     
       $cache = $cache_init -> deleteCache($key);

       break;
          
  }

  return $cached;
  
}





