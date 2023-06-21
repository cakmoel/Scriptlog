<?php defined('SCRIPTLOG') || die("Direct access not permitted");

use Medoo\Medoo;

/**
 * Class MedooInit
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * 
 */
class MedooInit
{

protected static $database;

public static function connect(array $options)
{
  self::$database = new Medoo($options);
  return self::$database;
}

}