<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class Registry
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

  /**
   * constructor
   */
  public function __construct()
  {
  }

  /**
   * get
   * 
   * @method get()
   * @static
   * @param string $key
   */
  public static function get($key)
  {
    // Check if the key exists
    if (!isset(self::$data[$key])) {
      // If the key does not exist, return null
      return null;
    }

    return self::$data[$key];
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
    // Set the value of the key
    self::$data[$key] = $value;
  }

  /**
   * setAll
   * 
   * @method public setAll()
   * @static
   * @param array $key
   * 
   */
  public static function setAll(array $key = [])
  {
    // Set all the values in the array
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
    // Checking if the key exists
    return isset(self::$data[$key]);
  }

  /**
   * registryObject
   *
   * @return void
   */
  public static function registerObject($object, $key)
  {
    // Create a new instance of the object
    $newObject = new $object(self::$objects);

    // Set the object int the registry
    self::$objects[$key] = $newObject;

  }

  /**
   * getObjectRegistered
   *
   * @param string $key
   * @return Object
   * 
   */
  public static function getObjectRegistered($key)
  {
    // Get the object from the registry
    return self::$objects[$key];
  }
}