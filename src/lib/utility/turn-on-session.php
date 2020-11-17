<?php
/**
 * turn_on_session()
 * 
 * Checking too old session ID and start session
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param number $life_time
 * @param string $session_name
 * @see https://www.php.net/manual/en/function.session-create-id.php
 * @return void
 * 
 */
function turn_on_session($session_handler, $life_time, $cookies_name, $path, $domain, $secure, $httponly)
{

 if (is_a($session_handler, 'SessionMaker')) {

    $session_handler->start();

    if (!$session_handler->isValid()) {

        $session_handler->forget();

    }

 }

  // Do not allow to use too old session ID
 if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - $life_time) {
        
      $session_handler->forget();

      $session_handler->start();
        
      set_cookies_scl($cookies_name, session_id(), $life_time, $path, $domain, $secure, $httponly);

      $session_handler->refresh();
      
 }
  
}


