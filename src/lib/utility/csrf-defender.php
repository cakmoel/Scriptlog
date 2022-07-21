<?php
/**
 * csrf_check_token
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $key
 * @param string $origin
 * @param string $timespan
 * @return boolean
 * 
 */
function csrf_check_token($key, $origin, $timespan = null)
{
  $check_csrf = CSRFGuard::check($key, $origin, true, $timespan, false);
  
  if ($check_csrf) {

    return true;

  } else {

    return false;

  }
  
}

/**
 * csrf_generate_token
 * 
 * @category function
 * @author M.Noermoehammad
 * @license 
 * @version 1.0
 * @param string $key
 * @return string
 * 
 */
function csrf_generate_token($key)
{
  $token = CSRFGuard::generate($key);
  return $token;
}