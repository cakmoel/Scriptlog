<?php

/**
 * File check-engine.php
 * 
 * @category  installation file check-engine.php
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * 
 */

use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\Browser;

/**
 * Checking PHP Version Function
 */
function check_php_version()
{

  return (version_compare(PHP_VERSION, '7.4', '>=')) ? true : false;
}

/**
 * check_mysql_version()
 * 
 * Checking mysql version
 * 
 * @category installation file
 * @param object|int $link
 * 
 */
function check_mysql_version($link, $min)
{

  if ($link instanceof mysqli) {

    $mysql_version = $link->server_version;

  } else {

    $mysql_version = mysqli_get_server_version($link);
  }

  preg_match("/^[0-9\.]+/", $mysql_version, $match);

  $mysql_version = isset($match[0]) ? $match[0] : "";

  return version_compare($mysql_version, $min) >= 0;

}

/**
 * check_os()
 * 
 * Checking Operating System
 * 
 */
function check_os()
{
  $os = new Os();

  if (($os->getName() === Os::LINUX) || ($os->getName() === Os::FREEBSD) ||
    ($os->getName() === Os::NETBSD) || ($os->getName() === Os::OPENBSD) ||
    ($os->getName() === Os::OPENSOLARIS) || ($os->getName() === Os::CHROME_OS) ||
    ($os->getName() === Os::WINDOWS) || ($os->getName() === Os::OSX)
  ) {

    return array("Operating_system" => $os->getName());
  }
}

/**
 * grab_browser()
 * 
 * instantiate browser object
 * 
 */
function grab_browser()
{
  return new Browser();
}

/**
 * check_browser()
 *
 * @return string
 * 
 */
function check_browser()
{

  return grab_browser()->getName();
}

/**
 * check_browser_version()
 * 
 * Checking Browser Version
 * 
 */
function check_browser_version()
{
  $browser = grab_browser();

  if (($browser->getName() === Browser::CHROME) && ($browser->getVersion() < 65)) {

    return true;
  } elseif (($browser->getName() === Browser::FIREFOX) && ($browser->getVersion() < 56.0)) {

    return true;
  } elseif (($browser->getName() === Browser::OPERA) && ($browser->getVersion() < 52.0)) {

    return true;
  } elseif (($browser->getName() === Browser::VIVALDI) && ($browser->getVersion() < 1.14)) {

    return true;
  } elseif (($browser->getName() === Browser::IE) && ($browser->getVersion() < 11)) {

    return true;
  } elseif (($browser->getName() === Browser::SAFARI) && ($browser->getVersion() < 13)) {

    return true;
  } elseif (($browser->getName() === Browser::EDGE) && ($browser->getVersion() < 75)) {

    return true;
  } elseif ($browser->getName() === 'Brave') {

    return true;
  } else {

    return false;
  }
}


/**
 * get_browser_version()
 * 
 * retrieving browser version
 * 
 */
function get_browser_Version()
{

  return grab_browser()->getVersion();
}

/**
 * check_web_server()
 * 
 * Checking Web Server
 * 
 */
function check_web_server()
{

  $get_web_server = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : $_SERVER['SERVER_NAME'];

  $slice = explode("/", $get_web_server);

  $webServer = isset($slice[0]) ? $slice[0] : '';

  $version = isset($slice[1]) ? $slice[1] : '';

  return array('WebServer' => $webServer, 'Version' => $version);
}

/**
 * check_main_dir()
 * 
 * Checking Main Engine
 * 
 * @return bool
 * 
 */
function check_main_dir()
{
  return (is_file(APP_PATH . '../lib/main.php')) ? true : false;
}

/**
 * check_loader()
 * 
 * Checking Load Engine
 * 
 * @return  bool
 * 
 */
function check_loader()
{
  return (file_exists(APP_PATH . '../lib/Autoloader.php')) ? true : false;
}

/**
 * Checking Log Directory. It is writable or not
 * 
 * @return bool
 * 
 */
function check_log_dir()
{

  $perms = fileperms(APP_PATH . '../public/log');

  if (is_dir(APP_PATH . '../public/log')) {

    return true;
  } elseif (is_writable(APP_PATH . '../public/log') || ($perms & 0x0080) || ($perms & 0x0010) || ($perms & 0x0002)) {

    return true;
  } else {

    return false;
  }
}

/**
 * Checking Theme Directory It is writeable or not
 */
function check_theme_dir()
{

  $perms = fileperms(APP_PATH . '../public/themes');

  if (is_dir(APP_PATH . '../public/themes')) {

    return true;
  } elseif (is_writable(APP_PATH . '../public/themes') || ($perms & 0x0080) || ($perms & 0x0010) || ($perms & 0x0002)) {

    return true;
  } else {

    return false;
  }
}

/**
 * Checking Cache Directory. It is writable or not
 * 
 */
function check_cache_dir()
{

  $perms = fileperms(APP_PATH . '../public/cache/');

  if (is_dir(APP_PATH . '../public/cache')) {

    return true;
  } elseif (is_writable(APP_PATH . '../public/cache') || ($perms & 0x0080) || ($perms & 0x0010) || ($perms & 0x0002)) {

    return true;
  } else {

    return false;
  }
}

/**
 * check_plugin_dir()
 * 
 * Checking lib Plugin Directory. It is writeable or not
 * 
 */
function check_plugin_dir()
{

  $perms = fileperms(APP_PATH . '../admin/plugins');

  if (is_dir(APP_PATH . '../admin/plugins')) {

    return true;
  } elseif (is_writable(APP_PATH . '../admin/plugins') || ($perms & 0x0080) || ($perms & 0x0010) || ($perms & 0x0002)) {

    return true;
  } else {

    return false;
  }
}

/**
 * Checking PCRE UTF-8
 * 
 */
function check_pcre_utf8()
{

  if (!@preg_match('/^.$/u', 'ñ')) {

    return true;
  } elseif (!@preg_match('/^\pL$/u', 'ñ')) {

    return true;
  } else {

    return false;
  }
}

/**
 * Checking SPL
 */
function check_spl_enabled($value)
{
  return (function_exists($value)) ? true : false;
}

/**
 * Checking filter_list function
 * whether enabled or not
 */
function check_filter_enabled()
{
  return (function_exists('filter_list')) ? true : false;
}

/**
 * Checking extension iconv
 * 
 * @return boolean
 * 
 */
function check_iconv_enabled()
{
  return (extension_loaded('iconv')) ? true : false;
}

/**
 * checking extension mbstring
 *
 * @return bool
 * 
 */
function check_mbstring_enabled()
{
  return (extension_loaded('mbstring')) ? true : false;
}

/**
 * check extension fileinfo
 *
 * @return bool
 */
function check_fileinfo_enabled()
{

  return (extension_loaded('fileinfo')) ? true : false;
}

/**
 * check_character_type()
 * 
 * Checking ctype_digit function exists or not
 * 
 */
function check_character_type()
{
  return (!function_exists('ctype_digit')) ? true : false;
}

/**
 * Checking server request global
 */
function check_uri_determination()
{

  return (isset($_SERVER['REQUEST_URI']) || isset($_SERVER['PHP_SELF']) || isset($_SERVER['PHP_INFO'])) ? true : false;
}

/**
 * Checking extension pdo_mysql and PDO class
 */
function check_pdo_mysql()
{
  return (extension_loaded('pdo_mysql') && class_exists('PDO')) ? true : false;
}

/**
 * Checking mysqli function
 */
function check_mysqli_enabled()
{
  return (function_exists('mysqli_connect')) ? true : false;
}

/**
 * Checking GD function
 */
function check_gd_enabled()
{
  return (function_exists('gd_info')) ? true : false;
}

/**
 * check_modrewrite()
 * 
 * Checking mod_rewrite functionality
 * 
 * @return bool
 * 
 */
function check_modrewrite()
{

  if (php_sapi_name() == 'apache' || php_sapi_name() == 'apache2handler' || php_sapi_name() == 'litespeed') {

    $apache_modules = (function_exists('apache_get_modules')  ? apache_get_modules() : []);

    if ((check_web_server()['WebServer'] == 'Apache') && (in_array('mod_rewrite', $apache_modules))) {

      return true;
    }

    if ((check_web_server()['WebServer'] == 'LiteSpeed') && (in_array('mod_rewrite', $apache_modules))) {

      return true;
    }
  }
}
