<?php
/**
 * File settings.php
 * 
 * @category  installation file settings.php
 * @package   SCRIPTLOG INSTALLATION
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * 
 */
ini_set('display_errors',1);
error_reporting(-1);
define('APP_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('APP_INC', 'include');

$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false ? 'http' : 'https';
$server_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

if (file_exists(APP_PATH . APP_INC . '/vendor/autoload.php')) {
    
  require(__DIR__ . '/vendor/autoload.php');
    
}

if (!ini_get('date.timezone')) {

  date_default_timezone_set('GMT');

}

if (!isset($_SESSION)) {
    
  session_start();
    
}

$errors = array();

ob_start();