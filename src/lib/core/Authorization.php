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
 * Sets the Authentication instance
 *
 * @param object $authentication Instance to set
 * @return void
 * @throws InvalidArgumentException If $authentication is not an instance of Authentication
 * 
 */
public static function setAuthInstance($authentication): void
{

  if (! ($authentication instanceof Authentication)) {
    throw new InvalidArgumentException('Argument must be an instance of Authentication');
  }

  self::$authentication = $authentication;
  
}

/**
 * getAuthInstance
 *
 * @return object
 * 
 */
public static function getAuthInstance(): ?Authentication
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
public static function authorizeRole(string $role): bool
{

  if (self::$authentication === null) {
    return false; 
  }
  
  return self::getAuthInstance()->userAccessControl($role);
}

/**
 * authorizeLevel
 * @return  string 
 */
public static function authorizeLevel(): string
{

 return self::getAuthInstance()->accessLevel();
  
}

}