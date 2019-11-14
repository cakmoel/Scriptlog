<?php
/**
 * SessionHandler Class
 * 
 * @package  SCRIPTLOG/LIB/CORE/SessionHandler
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * 
 */
class SessionHandler
{

/**
 * Initialize session
 * 
 */
public function __construct()
{
      
}

/**
 * set application session
 * 
 * @var string $key
 * @var string $value
 * 
 */
public function setAppSession($key, $value)
{
  $_SESSION[$key] = $value;
}

/**
 * get application session
 * 
 * @var string
 */
public function getAppSession($key)
{
  if(isset($_SESSION[$key])) {

    return $_SESSION[$key];

  } else {

    return false;

  }

}

public function removeAppSession($key) 
{
  unset($_SESSION[$key]);
}

public function destroyAppSession()
{
  $_SESSION = array();
  session_destroy();
  session_regenerate_id();
}

}