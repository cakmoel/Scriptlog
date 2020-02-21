<?php

/**
 * regenerate new session ID
 * Session ID must be regenerated when
 * User logged in
 * User logged out
 * Certain period has passed
 * 
 * @return void
 * 
 */
function regenerate_session()
{

 if (session_status() != PHP_SESSION_ACTIVE) {
      
     session_start();

 }

 $newsid = session_id();

 $_SESSION['deleted_time'] = time() - Authentication::COOKIE_EXPIRE;

 session_write_close();

 session_id($newsid);

 session_start();

 session_regenerate_id();

}