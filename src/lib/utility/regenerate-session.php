<?php

/**
 * regenerate new session ID
 * Session ID must be regenerated when
 * User logged in
 * Certain period has passed
 * 
 * @return void
 * 
 */
function regenerate_session()
{

 $old_session_id = session_id();
 
 $_SESSION['deleted_time'] = time() - Authentication::COOKIE_EXPIRE;

 session_regenerate_id();

 unset($_SESSION['deleted_time']);
 
 $new_session_id = session_id();
 
}

/**
 * is_session_started
 *
 * @see https://www.php.net/manual/en/function.session-status.php#113468
 * @return boolean
 * 
 */
function is_session_started()
{

 if(php_sapi_name() !== 'cli') {

    if(version_compare(phpversion(), '5.4.0', '>=')) {

       return session_status() === PHP_SESSION_ACTIVE ? true : false;

    } else {

       return session_id() === '' ? false : true;

    }

 }

return false;

}

// get session data
function get_session_data($session_name = 'scriptlog', $session_save_handler = 'files')
{

 $session_data = array();

 if (array_key_exists($session_name, $_COOKIE)) {

    $session_id = $_COOKIE[$session_name];

    $old_session_id = session_id();

    session_write_close();

    $old_session_save_handler = ini_get('session.save_handler');

    ini_set('session.save_handler', $session_save_handler);

    $old_session_name = session_name($session_name);

    session_id($session_id);

    session_start();

    $session_data = $_SESSION;

    session_write_close();

    ini_set('session.save_handler', $old_session_save_handler);

    session_name($old_session_name);

    session_id($old_session_id);

    session_start();

 }

 return $session_data;

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
