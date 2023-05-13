<?php
/**
 * File settings.php
 * 
 * @category  file settings.php define constant 
 *            and variables need by installation process
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * 
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('APP_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('APP_INC', 'include');

require_once __DIR__ . '/../../lib/vendor/autoload.php';  
require_once __DIR__ . '/../../lib/utility/is-ssl.php';
require_once __DIR__ . '/../../lib/utility/get-browser-name.php';
require_once __DIR__ . '/check-engine.php';

  
if (!ini_get('date.timezone')) {

  date_default_timezone_set('UTC');

}

if (false === is_ssl()) {

  $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
 
} else {

  $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'https' : 'http';

}

$server_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

if (!isset($_SESSION)) {
    
  session_start();
    
}

$errors = array();

ob_start();