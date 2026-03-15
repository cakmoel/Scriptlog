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
  $check_csrf = class_exists('CSRFGuard') ? CSRFGuard::check($key, $origin, true, $timespan, false) : "";
  
  return ($check_csrf) ? true : false;

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
  return (class_exists('CSRFGuard')) ? CSRFGuard::generate($key) : "";
}