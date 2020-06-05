<?php
/**
 * CSRF Check Token Function
 * 
 * @category function
 * @param string $key
 * @param string $origin
 * @param string $timespan
 * @return boolean
 * 
 */
function csrf_check_token($key, $origin, $timespan = null)
{
  $check_csrf = NoCSRF::check($key, $origin, true, $timespan, false);
  
  if ($check_csrf) {

    return true;

  } else {

    return false;

  }
  
}

/**
 * CSRF Generate Token Function
 * 
 * @category function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @param string $key
 * @return string
 * 
 */
function csrf_generate_token($key)
{
  $token = NoCSRF::generate($key);
  return $token;
}