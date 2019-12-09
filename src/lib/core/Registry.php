<?php 
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
 * Data registered
 * 
 * @property array $_data
 * @static
 * @var array
 */
 private static $data = array();
 
/**
 * get
 * 
 * @method get()
 * @static
 * @param string $key
 */
 public static function get($key)
 {
   return (isset(self::$data[$key]) ? self::$data[$key] : null);
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
 public static function setAll(array $key = array()) 
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
   return (isset(self::$data[$key]));  
 }
 
}