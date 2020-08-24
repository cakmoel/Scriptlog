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
 * set_session_cookies_key
 *
 * @category Function
 * @return string
 * 
 */
function set_session_cookies_key()
{

  $session_cookies_key = hash_hmac('sha384', app_info()['site_email'], hash('sha384', app_key().zend_ip_address(), true));
  
  return $session_cookies_key;

}

/**
 * is_cookies
 * 
 * @category Function
 * @param string $cookies
 * @return boolean
 * 
 */
function is_cookies($cookies)
{

  if (isset($_COOKIE[$cookies])) {

     foreach ($_COOKIE[$cookies] as $name => $value) {

        if (!empty($name)) {

            return $name;

        }

     }
     
  }

}

/**
 * set_cookie_path
 *
 * @category Function
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
 * set_cookies_scl
 * Support samesite cookie flag
 * 
 * @category Function
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @see https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict
 * @see https://stackoverflow.com/questions/58317981/php-setting-a-session-cookie-with-samesite
 * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
 * @see https://stackoverflow.com/questions/36877/how-do-you-set-up-use-httponly-cookies-in-php
 * @see https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite
 * @see https://www.php.net/manual/en/function.setcookie.php
 * @see https://www.php.net/setcookie
 * @see https://www.php.net/manual/en/features.cookies.php
 * @param string $name
 * @param string $value
 * @param string $expire
 * @param string $path
 * @param string $domain
 * @param bool $secure
 * @param bool $httponly
 * @param string $samesite
 * @return void
 * 
 */
function set_cookies_scl($name, $value, $expire, $path, $domain, $secure, $httponly, $samesite="Strict")
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