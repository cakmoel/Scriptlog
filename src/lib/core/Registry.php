<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Registry Class
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Registry
{

/**
 * objects
 *
 * @var array of objects
 * 
 */
private static $objects;

/**
 * Data registered
 * 
 * @property array $_data
 * @static
 * @var array
 */
 private static $data = [];
 
public function __construct() {}

/**
 * get
 * 
 * @method get()
 * @static
 * @param string $key
 */
 public static function get($key)
 {
   
  return isset(self::$data[$key]) ? self::$data[$key] : null;

 }
 
/**
 * set
 * 
 * @method public set()
 * @static
 * @param string $key
 * @param string $value
 * 
 */
 public static function set($key, $value)
 {
   self::$data[$key] = $value;
 }
 
/**
 * set All
 * 
 * @method public setAll()
 * @static
 * @param array $key
 * 
 */
 public static function setAll(array $key = []) 
 {
   self::$data = $key;
 }
 
/**
 * Is key set
 * Checking whether key set or not
 * 
 * @method public isKeySet()
 * @static
 * @param string $key
 * @return array
 * 
 */
 public static function isKeySet($key)
 {
   return isset(self::$data[$key]);  
 }
 
/**
 * registryObject
 *
 * @return void
 */
 public static function registerObject( $object, $key )
 {

   self::$objects[$key] = new $object(self::$objects);

 }

/**
 * getObjectRegistered
 *
 * @param string $key
 * @return Object
 * 
 */
 public static function getObjectRegistered( $key )
 {
   return self::$objects[ $key ];
 }

}