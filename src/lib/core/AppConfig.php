<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * AppConfig Class
 * 
 * Example:
 * AppConfig::writeConfiguration('config.php', array( 'dbhost' => 'foo' ));
 * $config = AppConfig::readConfiguration('config.php');
 * $config['dbhost'] = '127.0.0.1';
 * $config['dbuser'] = 'scriptlog';
 * AppConfig::writeConfiguration('config.php', $config);
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
class AppConfig
{

 private static $config;

 public static function readConfiguration($filename)
 {
 
    self::$config = include $filename;

    return self::$config;

 }

 public static function writeConfiguration($filename, array $config)
 {

   self::$config = var_export($config, true);

   file_put_contents($filename, "<?php return self::$config; ");

 }

}