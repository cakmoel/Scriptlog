<?php
/**
 * class Authorization
 * 
 * @category core class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class Authorization
{

private static $authentication;

public static function setAuthInstance($authentication)
{

  if (is_a($authentication, 'Authentication')) {

       self::$authentication = $authentication;

  }

}

public static function getAuthInstance()
{
  return self::$authentication;
}

public static function authorizeRole($role)
{
return self::getAuthInstance()->userAccessControl($role);
}

public static function authorizeLevel()
{
return self::getAuthInstance()->accessLevel();
}

}