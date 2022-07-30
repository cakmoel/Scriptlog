<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ScriptlogScanner
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class ScriptlogScanner
{

private static $directory;

private static $filter = [];

private static function clearScanStatus()
{

  if (version_compare(PHP_VERSION, '7.4', '>=')) {

      clearstatcache();

  } else {

      clearstatcache(true);
        
  }

}

public static function setDirectory($directory)
{
   self::$directory = $directory;
}

public static function getDirectory()
{
  return self::$directory;
}

public static function setFilter(array $filter = [])
{
  self::$filter = $filter;
}

public static function getFilter()
{
  return self::$filter;
}

public static function scan()
{

self::clearScanStatus();

return array_diff(scandir(self::getDirectory()), ['.', '..']);

}

}