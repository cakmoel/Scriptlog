<?php
/**
 * Function run_cache_engine
 * running cache extension for PHP
 * 
 * @param string $host
 * 
 */
function run_cache_engine()
{

  $cache = false;

  if(true === is_cache_ext()) {

     if(extension_loaded('memcached')) {

         $cache = true;
        
     } elseif(extension_loaded('apcu') && ini_get('apc.enabled')) {

         $cache = true;

     }
     
  }

  return $cache;
  
}

