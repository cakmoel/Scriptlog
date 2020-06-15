<?php
/**
 * is_cookies_secured function
 * checking whether a secure HTTPS connection enabled 
 *
 * @return boolean
 */
function is_cookies_secured()
{
  if(is_ssl() == true) {

      return true;

  } else {

      return false;

  }

}

/**
 * set_cookie_path function
 *
 * @return string
 * 
 */
function set_cookies_path()
{ 
 
  $root_path = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : "";
  $admin_path = dirname(__FILE__).'/../../admin/';
  $cookies_path = str_replace($root_path, '', $admin_path);
  return $cookies_path;
  
}

/**
 * set_cookies_samesite function
 * Support samesite cookie flag
 * 
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @see https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict
 * @see https://stackoverflow.com/questions/58317981/php-setting-a-session-cookie-with-samesite
 * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
 * @see https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite
 * @see https://www.php.net/manual/en/function.setcookie.php
 * @see https://www.php.net/manual/en/features.cookies.php
 * @param string $name
 * @param string $value
 * @param  $expire
 * @param [type] $path
 * @param [type] $domain
 * @param [type] $secure
 * @param [type] $httponly
 * @param [type] $samesite
 * @return void
 * 
 */
function set_cookies_scl($name, $value, $expire, $path, $domain, $secure, $httponly, $samesite="Lax")
{
  
  if(PHP_VERSION_ID <= 70300) {

    setcookie($name, $value, $expire, "$path; samesite=$samesite", $domain, $secure, $httponly);
     
  } else {

    setcookie($name, $value, [
       'expires' => $expire,
       'path' => $path,
       'domain' => $domain,
       'secure' => $secure,
       'httponly' => $httponly,
       'samesite' => $samesite,
    ]);

  }

}