<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class PassPhraseKey
 * 
 * @category Core Class 
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class PassPhraseKey
{

/**
 * passphrase
 *
 * @var string
 * 
 */
protected $passphrase;

/**
 * directory
 *
 * @var string
 * 
 */
private static $directory;

/**
 * filepath
 *
 * @var string
 * 
 */
private static $filepath;

/**
 * filename
 *
 * @var string
 * 
 */
private static $filename;

/**
 * savePassPhraseKey
 *
 * @param string $passphrase
 * @param string $filename
 * 
 */
public static function savePassPhraseKey($passphrase, $filename)
{

 if (isset($passphrase)) {

    static::$passphrase = $passphrase;

    if (false === self::checkingUserLevel()) {

      scriptlog_error("You are not allowed to do this procedure");

    } else {

      $fp = fopen(self::grabFilename($filename), 'w');
      fwrite($fp, $passphrase);
      fclose($fp);
      
    }
 }

}

/**
 * readPassPhraseKey
 * 
 * @param string $filename
 * 
 */
public static function readPassPhraseKey($filename)
{
  if (false === self::checkingUserLevel()) {
    
    scriptlog_error("You are not allowed to do this procedure");

  } else {

    $readPassPhraseKey = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? fopen($filename, "rb") : fopen($filename, "r");
    
    if (false === fgets($readPassPhraseKey, 4096)) {

      scriptlog_error("Error: Unexpected fget() fail\n");

    } else {

      echo fgets($readPassPhraseKey, 4096);

    }

    fclose($readPassPhraseKey);

  }
  
}

/**
 * checkingUserLevel
 * 
 */
private static function checkingUserLevel()
{
    return (self::grabUserLevel() == 'administrator' || self::grabUserLevel() == 'manager' 
    || self::grabUserLevel() == 'editor' || self::grabUserLevel() == 'author' 
    || self::grabUserLevel() == 'contributor') ? true : false;

}

/**
 * grabUserLevel
 *
 */
private  static function grabUserLevel()
{
 return user_privilege();
}

/**
 * grabDirectoryKey
 *
 */
private static function grabDirectoryKey()
{
  self::$directory = isset($_SERVER['DOCUMENT_ROOT']) ? htmlspecialchars($_SERVER['DOCUMENT_ROOT']) : "";

  return self::$directory;
}

/**
 * grabFilename
 *
 * @param string $filename
 * 
 */
private static function grabFilename($filename)
{
 
 (version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

  static::$filename = $filename;

  $path = [];
  $path = explode('/', self::grabDirectoryKey());
  static::$filepath = DIRECTORY_SEPARATOR . $path[1] . DIRECTORY_SEPARATOR . $path[2] . DIRECTORY_SEPARATOR . $path[3] . DIRECTORY_SEPARATOR;
  
  if ((!file_exists(static::$filepath . static::$filename)) || (!is_writable(static::$filepath.static::$filename))) {

    scriptlog_error("Permission denied");

  } else {

    return static::$filepath.static::$filename;
  }
 
}

}