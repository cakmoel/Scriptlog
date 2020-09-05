<?php

/**
 * regenerate new session ID
 * Session ID must be regenerated when
 * User logged in
 * Certain period has passed
 * 
 * @category function
 * @return void
 * 
 */
function regenerate_session()
{

 $old_session_id = session_id();
 
 $_SESSION['deleted_time'] = time() - Authentication::COOKIE_EXPIRE;

 session_regenerate_id(true);

 unset($_SESSION['deleted_time']);
 
 $new_session_id = session_id();
 
}

/**
 * clear_duplicate_cookies function
 * 
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
