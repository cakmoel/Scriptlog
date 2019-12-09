<?php
/**
 * Function is_cache_ext
 * Checking whether cache extension 
 * installed for PHP
 * 
 * @category Function
 * @return bool
 * 
 */
function is_cache_ext()
{
  
  $cache_extension_enabled = false;

  if(defined(APP_CACHE) && APP_CACHE == true) {
       
     $cache_extension_enabled = true;

  } else {

     $cache_extension_enabled = false;

  }

  return $cache_extension_enabled;

}