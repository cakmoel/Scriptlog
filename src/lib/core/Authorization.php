<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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

/**
 * authentication
 *
 * @var object
 * @static
 * 
 */
private static $authentication;

public function __construct(Authentication $authentication)
{
   self::$authentication = $authentication;
}

/**
 * setAuthInstance
 *
 * @param object $authentication
 * @return void
 * 
 */
public static function setAuthInstance($authentication)
{

  if (is_a($authentication, 'Authentication')) {

      self::$authentication = $authentication;

  }

}

/**
 * getAuthInstance
 *
 * @return object
 * 
 */
public static function getAuthInstance()
{
  return self::$authentication;
}

/**
 * authorizeRole
 *
 * @param string $role
 * @return false|true
 * 
 */
public static function authorizeRole($role)
{

 return self::getAuthInstance()->userAccessControl($role);

}

/**
 * authorizeLevel
 * @return  string 
 */
public static function authorizeLevel()
{

 return self::getAuthInstance()->accessLevel();
  
}

}