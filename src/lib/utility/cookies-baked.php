<?php
/**
 * is_cookies_secured function
 * checking whether a secure HTTPS connection enabled 
 *
 * @return boolean
 */
function is_cookies_secured()
{
  if(is_ssl() == true) {

      return true;

  } else {

      return false;

  }

}

function set_cookies_path()
{ 
 
  $root_path = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : "";
  $admin_path = dirname(__FILE__).'/../../admin/';
  $cookies_path = str_replace($root_path, '', $admin_path);
  return $cookies_path;
  
}