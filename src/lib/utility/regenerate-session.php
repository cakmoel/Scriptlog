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

 if (session_status() != PHP_SESSION_ACTIVE) {
      
   session_start();
  
 }

 if (isset($_SESSION['deleted_time']) && time() - $_SESSION['deleted_time'] > Authentication::COOKIE_EXPIRE) {
   
   session_unset();
   
   session_destroy();
   
   session_write_close();
   
   session_regenerate_id(true);

}

$newid = (version_compare(phpversion(), "7.1.0", ">=")) ? session_create_id() : session_id();

$_SESSION['deleted_time'] = time();

session_commit();

session_regenerate_id();

unset($_SESSION['deleted_time']);

session_id($newid);

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

/**
 * get_session_data()
 *
 * @param string $session_name
 * @param string $session_save_handle
 * @see https://www.php.net/manual/en/ref.session.php#96309
 * @return void
 * 
 */
function get_session_data($session_name = '_scriptlog', $session_save_handler = 'files')
{

   $session_data = array();
   # did we get told what the old session id was? we can't continue it without that info
   if (array_key_exists($session_name, $_COOKIE)) {
       # save current session id
       $session_id = $_COOKIE[$session_name];
       $old_session_id = session_id();
      
       # write and close current session
       session_write_close();
      
       # grab old save handler, and switch to files
       $old_session_save_handler = ini_get('session.save_handler');
       ini_set('session.save_handler', $session_save_handler);
      
       # now we can switch the session over, capturing the old session name
       $old_session_name = session_name($session_name);
       session_id($session_id);
       session_start();
      
       # get the desired session data
       $session_data = $_SESSION;
      
       # close this session, switch back to the original handler, then restart the old session
       session_write_close();
       ini_set('session.save_handler', $old_session_save_handler);
       session_name($old_session_name);
       session_id($old_session_id);
       session_start();
   }
  
   # now return the data we just retrieved
   return $session_data;

}