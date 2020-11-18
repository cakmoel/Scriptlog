<?php

/**
 * regenerate_session
 * Session ID must be regenerated when
 * User logged in
 * Certain period has passed
 * 
 * @category function
 * @author M.Noermoehammad
 * @see https://www.php.net/manual/en/function.session-regenerate-id.php
 * @license MIT
 * @version 1.0
 * @return void
 * 
 */
function regenerate_session()
{

 if (isset($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - Authentication::COOKIE_EXPIRE) {
  
   unset($_SESSION['user_login']);

 }
 
 $oldid = session_id();

 $_SESSION['deleted_time'] = time();

 session_regenerate_id();

 unset($_SESSION['deleted_time']);

 $newid = session_id();

 return $newid;

}

/**
 * clear_duplicate_cookies
 * 
 * @category function
 * @see https://www.php.net/manual/en/function.session-start.php#117157
 * @return void
 * 
 */
function clear_duplicate_cookies()
{

 if (headers_sent()) {

    return;

 }

 $cookies = array();

 foreach (headers_list() as $header) {

    if (strpos($header, 'Set-Cookie:') === 0) {

        $cookies[] = $header;

    }

 }

 header_remove('Set-Cookie');

 foreach (array_unique($cookies) as $cookie) {

    header($cookie, false);

 }
 
}