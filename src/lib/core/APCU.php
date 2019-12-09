<?php
/**
 * Class APCU
 * 
 * @category Class APCU extends Cache
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
class APCU extends Cache
{

/**
 * APCu read cache
 * fetch a stored variable from cache
 * 
 * @return string if apcu exists -- true or
 * @return  null 
 */
 public function readCache($key)
 {
   if (apcu_exists($key)) {

      return apcu_fetch($key);

   } else {

      return null;

   }

 }

/**
 * APCu write cache 
 * Cache a variable in the data store
 * 
 * @return boolean on success or false on failure
 * 
 */
 public function writeCache($key, $value, $expiration)
 {
   return apcu_store($key,$value, $expiration);
 }

/**
 * Remove cache
 * Delete cache in the data store
 * 
 * @param string $key
 */
 public function removeCache($key)
 {
   return apcu_delete($key);
 }

}