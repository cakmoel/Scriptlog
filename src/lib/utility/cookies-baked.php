<?php
/**
 * is_cookies_secured()
 * 
 * checking whether a secure HTTPS connection enabled 
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return boolean
 * 
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
 * set_session_cookies_key()
 *
 * generating session cookies key
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function set_session_cookies_key($app_email, $app_key)
{

  $session_cookies_key = hash_hmac('sha384', $app_email.$app_key.get_ip_address(), hash('sha384', $app_email.$app_key.get_ip_address(), true));
  
  return $session_cookies_key;

}

/**
 * is_cookies()
 * 
 * checking set array cookies that have array elements
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param array $cookies
 * @return string
 * 
 */
function is_cookies(array $cookies)
{

  if (isset($_COOKIE[$cookies])) {

     foreach ($_COOKIE[$cookies] as $name => $value) {

        if (!empty($name)) {

            $name = safe_html($name);
            
            return $name;

        }

     }
     
  }

  return false;
  
}

/**
 * set_cookie_path()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function set_cookies_path()
{ 
 
 $root_path = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : "";
 
 $admin_path = dirname(__FILE__).'/../../'.APP_ADMIN;
 
 $cookies_path = str_replace($root_path, '', $admin_path);
 
 return $cookies_path;
  
}

/**
 * set_cookies_scl()
 * 
 * Supporting samesite cookie flag
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @see https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict
 * @see https://stackoverflow.com/questions/58317981/php-setting-a-session-cookie-with-samesite
 * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
 * @see https://stackoverflow.com/questions/36877/how-do-you-set-up-use-httponly-cookies-in-php
 * @see https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @see https://www.php.net/manual/en/function.setcookie.php
 * @see https://www.php.net/setcookie
 * @see https://www.php.net/manual/en/features.cookies.php
 * @see https://scotthelme.co.uk/csrf-is-really-dead/
 * @see https://stackoverflow.com/questions/1354999/keep-me-logged-in-the-best-approach/17266448#17266448
 * @param string $name
 * @param string $value
 * @param string $expire
 * @param string $path
 * @param string $domain
 * @param bool $secure
 * @param bool $httponly
 * @param string $samesite
 * @return bool
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